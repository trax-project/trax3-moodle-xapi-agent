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

use block_trax_xapi\exceptions\client_exception;
use block_trax_xapi\config;

class client {

    /**
     * No error.
     */
    const STATUS_PENDING = 0;

    /**
     * Add a list of statements to the queue.
     *
     * @param int $lrsnum
     * @param array $statements
     * @return void
     */
    public static function queue(int $lrsnum, array $statements) {
        global $DB;

        $records = array_map(function ($statement) use ($lrsnum) {
            return [
                'lrs' => $lrsnum,
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
     * @return int
     */
    public static function queue_size(int $lrsnum,) {
        global $DB;

        $sql = "
            SELECT COUNT('id')
            FROM {block_trax_xapi_client_queue}
            WHERE status = ?
        ";
        return $DB->count_records_sql($sql, [self::STATUS_PENDING]);
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
     * Flush the queue of statements for a given LRS.
     *
     * @param int $lrsnum
     * @return void
     */
    public static function flush_lrs(int $lrsnum) {
        global $DB;

        while (1) {
            $sql = "
                SELECT *
                FROM {block_trax_xapi_client_queue}
                WHERE lrs = ? AND status = ?
                ORDER BY id
            ";
            $records = $DB->get_records_sql($sql, [$lrsnum, self::STATUS_PENDING], 0, config::xapi_batch_size());

            if (empty($records)) {
                return;
            }

            $statements = array_map(function ($record) {
                return json_decode($record->statement);
            }, $records);

            self::send($lrsnum, $statements);
            
            $ids = array_map(function ($record) {
                return $record->id;
            }, $records);

            $DB->delete_records_select('block_trax_xapi_client_queue', 'id IN (' . implode(',', $ids) . ')');

            self::update_client_status($lrsnum);
        }        
    }

    /**
     * Update client status.
     *
     * @param int $lrsnum
     * @return void
     */
    public static function update_client_status(int $lrsnum) {
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

    /**
     * Send a list of statements without going thru the queue.
     *
     * @param int $lrsnum
     * @param array $statements
     * @return void
     * @throws \block_trax_xapi\exceptions\client_exception
     */
    public static function send(int $lrsnum, array $statements) {
        $statements = array_values($statements);
        $lrs = new lrs($lrsnum);

        // Post the statements.
        try {
            $response = $lrs->statements()->post($statements);
        } catch (\Exception $e) {
            // Error from the HTTP client.
            logger::log_http_error($lrsnum, $lrs->endpoint(), 'statements', 'post', $statements, $lrs->headers(), $e);
            throw new client_exception();
        }

        // Error returned from the LRS.
        if ($response->code >= 400) {
            logger::log_lrs_error($lrsnum, 'statements', 'post', $statements, $response);
            throw new client_exception();
        }
    }
}
