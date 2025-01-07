<?php
// This file is part of the TRAX xAPI Agent plugin for Moodle.
//
// This plugin is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This plugin is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * TRAX xAPI Agent plugin.
 *
 * @package    block_trax_xapi
 * @copyright  2024 Sébastien Fraysse <sebastien@fraysse.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_trax_xapi;

defined('MOODLE_INTERNAL') || die();

use block_trax_xapi\exceptions\lrs_response_exception;
use block_trax_xapi\exceptions\lrs_client_exception;
use block_trax_xapi\config;
use block_trax_xapi\errors;

class client {

    /**
     * No error.
     */
    const STATUS_PENDING = 1;

    /**
     * No error.
     */
    const STATUS_ERROR_CLIENT = 2;

    /**
     * No error.
     */
    const STATUS_ERROR_LRS = 3;

    /**
     * Add a list of statements to the queue.
     *
     * @param int $lrsnum
     * @param int $courseid
     * @param array $statements
     * @return void
     */
    public static function queue(int $lrsnum, int $courseid, array $statements) {
        global $DB;

        $records = array_map(function ($statement) use ($lrsnum, $courseid) {
            return [
                'lrs' => $lrsnum,
                'courseid' => $courseid,
                'status' => self::STATUS_PENDING,
                'statement' => json_encode($statement),
                'timestamp' => time(),
            ];
        }, $statements);

        $DB->insert_records('block_trax_xapi_client_queue', $records);
    }

    /**
     * Return the size of the statements queue.
     *
     * @param int $lrsnum
     * @param int $courseid
     * @return int
     */
    public static function queue_size(int $lrsnum, int $courseid = null) {
        global $DB;

        if (isset($courseid)) {
            $sql = "
                SELECT COUNT('id')
                FROM {block_trax_xapi_client_queue}
                WHERE status = ? AND lrs = ? AND courseid = ?
            ";
            return $DB->count_records_sql($sql, [self::STATUS_PENDING, $lrsnum, $courseid]);
        } else {
            $sql = "
                SELECT COUNT('id')
                FROM {block_trax_xapi_client_queue}
                WHERE status = ? AND lrs = ?
            ";
            return $DB->count_records_sql($sql, [self::STATUS_PENDING, $lrsnum]);
        }
    }

    /**
     * Flush the queue of statements.
     *
     * @return void
     */
    public static function flush() {
        self::flush_lrs(config::LRS_PRODUCTION);
        self::flush_lrs(config::LRS_TEST);
    }

    /**
     * Retry the queue of statements for a given LRS.
     *
     * @param int $lrsnum
     * @return void
     */
    public static function retry_lrs(int $lrsnum) {
        errors::delete_client_logs($lrsnum);
        self::flush_lrs($lrsnum, null, self::STATUS_ERROR_CLIENT);
        self::flush_lrs($lrsnum, null, self::STATUS_ERROR_LRS);
    }

    /**
     * Flush the queue of statements for a given LRS.
     *
     * @param int $lrsnum
     * @param int $courseid
     * @return void
     */
    public static function flush_lrs(int $lrsnum, int $courseid = null, int $status = self::STATUS_PENDING) {
        global $DB;

        // Defined the first and last ids.
        $firstid = 0;
        if (isset($courseid)) {
            $sql = "
                SELECT *
                FROM {block_trax_xapi_client_queue}
                WHERE lrs = ? AND courseid = ? AND status = ?
                ORDER BY id DESC
            ";
            $records = $DB->get_records_sql($sql, [$lrsnum, $courseid, $status, $firstid], 0, 1);
        } else {
            $sql = "
                SELECT *
                FROM {block_trax_xapi_client_queue}
                WHERE lrs = ? AND status = ?
                ORDER BY id DESC
            ";
            $records = $DB->get_records_sql($sql, [$lrsnum, $status, $firstid], 0, 1);
        }
        if (empty($records)) {
            return;
        }
        $lastrecord = end($records);
        $lastid = $lastrecord->id;

        while (1) {
            if (isset($courseid)) {
                $sql = "
                    SELECT *
                    FROM {block_trax_xapi_client_queue}
                    WHERE lrs = ? AND courseid = ? AND status = ? AND id > ? AND id <= ?
                    ORDER BY id
                ";
                $records = $DB->get_records_sql($sql, [$lrsnum, $courseid, $status, $firstid, $lastid], 0, config::xapi_batch_size());
            } else {
                $sql = "
                    SELECT *
                    FROM {block_trax_xapi_client_queue}
                    WHERE lrs = ? AND status = ? AND id > ? AND id <= ?
                    ORDER BY id
                ";
                $records = $DB->get_records_sql($sql, [$lrsnum, $status, $firstid, $lastid], 0, config::xapi_batch_size());
            }

            if (empty($records)) {
                return;
            }

            $firstid = $records[array_key_last($records)]->id;

            $statements = array_map(function ($record) {
                return json_decode($record->statement);
            }, $records);

            try {
                self::send($lrsnum, $statements);
            } catch (lrs_client_exception $e) {
                // We don't want to abort the process. There will be an error log.
                self::update_queue_items($records);
                continue;
            } catch (lrs_response_exception $e) {
                // We don't want to abort the process. There will be an error log.
                self::update_queue_items($records, $e->client_response->code);
                continue;
            } catch (\Exception $e) {
                // Unwaited exception. We want to see what happens because there is no log for that.
                throw $e;
            }

            self::delete_queue_items($records);
            self::update_client_status($lrsnum);
        }        
    }

    /**
     * Clear the queue of statements for a given LRS.
     *
     * @param int $lrsnum
     * @param int $courseid
     * @return void
     */
    public static function clear_lrs(int $lrsnum, int $courseid = null) {
        global $DB;
        if (isset($courseid)) {
            $DB->delete_records('block_trax_xapi_client_queue', ['lrs' => $lrsnum, 'courseid' => $courseid, 'status' => self::STATUS_PENDING]); 
        } else {
            $DB->delete_records('block_trax_xapi_client_queue', ['lrs' => $lrsnum, 'status' => self::STATUS_PENDING]); 
        }
    }

    /**
     * Clear the errors for a given LRS.
     *
     * @param int $lrsnum
     * @return void
     */
    public static function clear_lrs_errors(int $lrsnum) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        errors::delete_client_logs($lrsnum);
        $DB->delete_records('block_trax_xapi_client_queue', ['lrs' => $lrsnum, 'status' => self::STATUS_ERROR_CLIENT]);
        $DB->delete_records('block_trax_xapi_client_queue', ['lrs' => $lrsnum, 'status' => self::STATUS_ERROR_LRS]);

        $transaction->allow_commit();
    }

    /**
     * Send a list of statements without going thru the queue.
     *
     * @param int $lrsnum
     * @param array $statements
     * @return void
     * @throws \block_trax_xapi\exceptions\lrs_client_exception
     * @throws \block_trax_xapi\exceptions\lrs_response_exception
     */
    public static function send(int $lrsnum, array $statements) {
        $statements = array_values($statements);
        $lrs = new lrs($lrsnum);

        // Post the statements.
        try {
            $response = $lrs->statements()->post($statements);
        } catch (\Exception $e) {
            // Error from the HTTP client.
            errors::log_lrs_client_error($lrsnum, $lrs->endpoint(), 'statements', 'post', $statements, $lrs->headers(), $e);
            throw new lrs_client_exception($lrsnum, $lrs->endpoint(), 'statements', 'post', $statements, $lrs->headers(), $e);
        }

        // Error returned from the LRS.
        if ($response->code >= 400) {
            errors::log_lrs_response_error($lrsnum, $lrs->endpoint(), 'statements', 'post', $statements, $response);
            throw new lrs_response_exception($lrsnum, $lrs->endpoint(), 'statements', 'post', $statements, $response);
        }
    }

    /**
     * Update the queue of statements with a given error code.
     *
     * @param array $records
     * @param int $code
     * @return void
     */
    protected static function update_queue_items(array $records, int $code = 0) {
        global $DB;

        $ids = array_map(function ($record) {
            return $record->id;
        }, $records);

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records_select('block_trax_xapi_client_queue', 'id IN (' . implode(',', $ids) . ')');

        $records = array_map(function ($record) use ($code) {
            $record->status = $code == 0 ? self::STATUS_ERROR_CLIENT : self::STATUS_ERROR_LRS;
            $record->error = $code;
            return $record;
        }, $records);

        $DB->insert_records('block_trax_xapi_client_queue', $records);

        $transaction->allow_commit();
    }

    /**
     * Delete the queue of statements.
     *
     * @param array $records
     * @return void
     */
    protected static function delete_queue_items(array $records) {
        global $DB;

        $ids = array_map(function ($record) {
            return $record->id;
        }, $records);

        $DB->delete_records_select('block_trax_xapi_client_queue', 'id IN (' . implode(',', $ids) . ')');
    }

    /**
     * Update client status.
     *
     * @param int $lrsnum
     * @return void
     */
    protected static function update_client_status(int $lrsnum) {
        global $DB;

        if (!$status = $DB->get_record('block_trax_xapi_client_status', ['lrs' => $lrsnum])) {
            $DB->insert_record('block_trax_xapi_client_status', [
                'lrs' => $lrsnum,
                'timestamp' => time(),
            ]);
        } else {
            $status->timestamp = time();
            $DB->update_record('block_trax_xapi_client_status', $status);
        }
    }
}
