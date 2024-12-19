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

namespace block_trax_xapi\sources\scorm;

defined('MOODLE_INTERNAL') || die();

use block_trax_xapi\config;
use block_trax_xapi\converter;
use block_trax_xapi\client;
use block_trax_xapi\exceptions\client_exception;

class scanner {

    /**
     * Run the SCORM scanner.
     *
     * @return void
     */
    public static function run() {
        foreach (config::scorm_course_configs() as $courseid => $config) {
            try {
                self::scan_course_data($courseid, $config);
                client::flush($config->lrs);
            } catch (client_exception $e) {
                return;
            }
        }
    }

    /**
     * Scan the SCORM data of a given course.
     *
     * @param int $courseid
     * @param object $config
     * @return void
     */
    protected static function scan_course_data(int $courseid, object $config) {
        global $DB;

        // Get the course status.
        if (!$status = $DB->get_record('block_trax_xapi_scorm_status', ['courseid' => $courseid, 'lrs' => $config->lrs])) {
            $status = (object)[
                'courseid' => $courseid,
                'lrs' => $config->lrs,
                'launchedtimestamp' => 0,
                'completedtimestamp' => 0,
                'assessedtimestamp' => 0,
                'interactedtimestamp' => 0,
                'timestamp' => time(),
            ];
            $status->id = $DB->insert_record('block_trax_xapi_scorm_status', $status, true);
        }

        self::process_launched_data($courseid, $config, $status);
        self::process_completed_data($courseid, $config, $status);
        self::process_assessed_data($courseid, $config, $status);
    }

    /**
     * Process the launched events from SCORM attempts.
     *
     * @param int $courseid
     * @param object $config
     * @param object $status
     * @return void
     */
    protected static function process_launched_data(int $courseid, object $config, object $status) {
        global $DB;

        // Defined the starting timestamp.
        $from = max(
            $status->launchedtimestamp,
            strtotime(\DateTime::createFromFormat('d/m/Y', $config->scorm_from)->format('d-m-Y'))
        );

        // Get the course SCOs.
        $attempts = $DB->get_records_sql("
            SELECT attempts.id, att.userid, attempts.attemptid, scorm.course as courseid, scorm.id as scormid, scoes.id as scoid, attempts.timemodified as timestamp
            FROM {scorm} scorm
            JOIN {scorm_scoes} scoes ON scoes.scorm = scorm.id
            JOIN {scorm_scoes_value} attempts ON attempts.scoid = scoes.id
            JOIN {scorm_element} elt ON attempts.elementid = elt.id
            JOIN {scorm_attempt} att ON attempts.attemptid = att.id
            LEFT JOIN {block_trax_xapi_scos_status} status ON attempts.scoid = status.scoid AND attempts.attemptid = status.attemptid AND status.lrs = :lrs
            WHERE scorm.course = :courseid
                AND elt.element = :element
                AND attempts.timemodified > :from
                AND status.status IS NULL
            ORDER BY attempts.timemodified ASC
        ", [
            'lrs' => $config->lrs,
            'courseid' => $courseid,
            'element' => 'x.start.time',
            'from' => $from,
        ]);

        if (empty($attempts)) {
            return;
        }

        // Convert the attemps.
        $statements = converter::convert_scorm_attempts($attempts, 'launched', $config->lrs);

        // Send the statements.
        if (count($statements) > 0) {
            client::send($config->lrs, $statements);
        }

        $transaction = $DB->start_delegated_transaction();

        // Update the SCORM status.
        $last_attempt = end($attempts);
        $status->launchedtimestamp = $last_attempt->timestamp;
        $status->timestamp = time();
        $DB->update_record('block_trax_xapi_scorm_status', $status);

        // Update the SCOs status.
        $records = array_map(function ($attempt) use ($config, $courseid) {
            return (object)[
                'courseid' => $courseid,
                'lrs' => $config->lrs,
                'attemptid' => $attempt->attemptid,
                'scoid' => $attempt->scoid,
                'status' => config::SCORM_STATUS_LAUNCHED,
            ];
        }, $attempts);
        $DB->insert_records('block_trax_xapi_scos_status', $records);

        $transaction->allow_commit();
    }

    /**
     * Process the completed events from SCORM attempts.
     *
     * @param int $courseid
     * @param object $config
     * @param object $status
     * @return void
     */
    protected static function process_completed_data(int $courseid, object $config, object $status) {
        global $DB;

        // Defined the starting timestamp.
        $from = max(
            $status->completedtimestamp,
            strtotime(\DateTime::createFromFormat('d/m/Y', $config->scorm_from)->format('d-m-Y'))
        );

        // Get the course SCOs.
        $records = $DB->get_records_sql("
            SELECT attempts.id, att.userid, attempts.attemptid, scorm.course as courseid, scorm.id as scormid, scoes.id as scoid, status.id as statusid,
                elt.element, attempts.value, attempts.timemodified as timestamp
            FROM {scorm} scorm
            JOIN {scorm_scoes} scoes ON scoes.scorm = scorm.id
            JOIN {scorm_scoes_value} attempts ON attempts.scoid = scoes.id
            JOIN {scorm_element} elt ON attempts.elementid = elt.id
            JOIN {scorm_attempt} att ON attempts.attemptid = att.id
            LEFT JOIN {block_trax_xapi_scos_status} status ON attempts.scoid = status.scoid AND attempts.attemptid = status.attemptid AND status.lrs = :lrs
            WHERE scorm.course = :courseid
                AND (elt.element = 'cmi.completion_status'
                    OR elt.element = 'cmi.total_time'
                )
                AND attempts.timemodified > :from
                AND status.status = :status
            ORDER BY attempts.timemodified ASC
        ", [
            'lrs' => $config->lrs,
            'courseid' => $courseid,
            'from' => $from,
            'status' => config::SCORM_STATUS_LAUNCHED,
        ]);

        $attempts = array_filter(self::attempts_from_records($records), function ($attempt) {
            return isset($attempt->values['cmi.completion_status']) && $attempt->values['cmi.completion_status'] == 'completed';
        });
        
        if (empty($attempts)) {
            return;
        }

        // Convert the attemps.
        $statements = converter::convert_scorm_attempts($attempts, 'completed', $config->lrs);

        // Send the statements.
        if (count($statements) > 0) {
            client::send($config->lrs, $statements);
        }

        $transaction = $DB->start_delegated_transaction();

        // Update the SCORM status.
        $last_record = end($records);
        $status->completedtimestamp = $last_record->timestamp;
        $status->timestamp = time();
        $DB->update_record('block_trax_xapi_scorm_status', $status);

        // Delete the SCOs status.
        $ids = array_map(function ($attempt) {
            return $attempt->statusid;
        }, $attempts);

        $DB->delete_records_select('block_trax_xapi_scos_status', 'id IN (' . implode(',', $ids) . ')');

        // Insert the new SCOs status.
        $records = array_map(function ($attempt) use ($config, $courseid) {
            return (object)[
                'courseid' => $courseid,
                'lrs' => $config->lrs,
                'attemptid' => $attempt->attemptid,
                'scoid' => $attempt->scoid,
                'status' => config::SCORM_STATUS_COMPLETED,
            ];
        }, $attempts);

        $DB->insert_records('block_trax_xapi_scos_status', $records);

        $transaction->allow_commit();
    }

    /**
     * Process the completed events from SCORM attempts.
     *
     * @param int $courseid
     * @param object $config
     * @param object $status
     * @return void
     */
    protected static function process_assessed_data(int $courseid, object $config, object $status) {
        global $DB;

        // Defined the starting timestamp.
        $from = max(
            $status->assessedtimestamp,
            strtotime(\DateTime::createFromFormat('d/m/Y', $config->scorm_from)->format('d-m-Y'))
        );

        // Get the course SCOs.
        $records = $DB->get_records_sql("
            SELECT attempts.id, att.userid, attempts.attemptid, scorm.course as courseid, scorm.id as scormid, scoes.id as scoid, status.id as statusid,
                elt.element, attempts.value, attempts.timemodified as timestamp
            FROM {scorm} scorm
            JOIN {scorm_scoes} scoes ON scoes.scorm = scorm.id
            JOIN {scorm_scoes_value} attempts ON attempts.scoid = scoes.id
            JOIN {scorm_element} elt ON attempts.elementid = elt.id
            JOIN {scorm_attempt} att ON attempts.attemptid = att.id
            LEFT JOIN {block_trax_xapi_scos_status} status ON attempts.scoid = status.scoid AND attempts.attemptid = status.attemptid AND status.lrs = :lrs
            WHERE scorm.course = :courseid
                AND (elt.element = 'cmi.success_status'
                    OR elt.element = 'cmi.score.min'
                    OR elt.element = 'cmi.score.max'
                    OR elt.element = 'cmi.score.raw'
                    OR elt.element = 'cmi.score.scaled'
                    OR elt.element = 'cmi.total_time'
                )
                AND attempts.timemodified > :from
                AND status.status = :status
            ORDER BY attempts.timemodified ASC
        ", [
            'lrs' => $config->lrs,
            'courseid' => $courseid,
            'from' => $from,
            'status' => config::SCORM_STATUS_COMPLETED,
        ]);

        $attempts = array_filter(self::attempts_from_records($records), function ($attempt) {
            return isset($attempt->values['cmi.success_status']) && (
                $attempt->values['cmi.success_status'] == 'passed' || $attempt->values['cmi.success_status'] == 'failed'
            );
        });
        
        if (empty($attempts)) {
            return;
        }

        // Convert the attemps.
        $statements = converter::convert_scorm_attempts($attempts, 'assessed', $config->lrs);

        // Send the statements.
        if (count($statements) > 0) {
            client::send($config->lrs, $statements);
        }

        $transaction = $DB->start_delegated_transaction();

        // Update the SCORM status.
        $last_record = end($records);
        $status->assessedtimestamp = $last_record->timestamp;
        $status->timestamp = time();
        $DB->update_record('block_trax_xapi_scorm_status', $status);

        // Delete the SCOs status.
        $ids = array_map(function ($attempt) {
            return $attempt->statusid;
        }, $attempts);

        $DB->delete_records_select('block_trax_xapi_scos_status', 'id IN (' . implode(',', $ids) . ')');

        // Insert the new SCOs status.
        $records = array_map(function ($attempt) use ($config, $courseid) {
            return (object)[
                'courseid' => $courseid,
                'lrs' => $config->lrs,
                'attemptid' => $attempt->attemptid,
                'scoid' => $attempt->scoid,
                'status' => config::SCORM_STATUS_ASSESSED,
            ];
        }, $attempts);

        $DB->insert_records('block_trax_xapi_scos_status', $records);

        $transaction->allow_commit();
    }

    /**
     * Group SCORM records by attempts.
     *
     * @param array $records
     * @return array
     */
    protected static function attempts_from_records(array $records) {
        $attempts = [];
        foreach ($records as $record) {
            if (!isset($attempts[$record->attemptid])) {
                $attempts[$record->attemptid] = $record;
                $attempts[$record->attemptid]->values = [$record->element => $record->value];
                unset($attempts[$record->attemptid]->element);
                unset($attempts[$record->attemptid]->value);
            } else {
                $attempts[$record->attemptid]->values[$record->element] = $record->value;
            }
        }
        return array_values($attempts);
    }
}
