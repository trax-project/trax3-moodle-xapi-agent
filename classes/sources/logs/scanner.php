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

namespace block_trax_xapi\sources\logs;

defined('MOODLE_INTERNAL') || die();

use block_trax_xapi\config;
use block_trax_xapi\selector;
use block_trax_xapi\converter;
use block_trax_xapi\client;
use block_trax_xapi\errors;

class scanner {

    /**
     * Run the log store scanner.
     *
     * @param int $lrs
     * @param int $courseid
     * @return void
     */
    public static function run(int $lrs = null, int $courseid = null) {
        
        // First, system level events.
        if (!isset($courseid) || $courseid == 0) {
            $config = config::system_events_config();
            if (!isset($lrs) || $lrs == $config->lrs) {
                self::scan_course_logs(0, $config);
            }
        }

        // Now, course events.
        foreach (config::log_store_course_configs() as $id => $config) {
            if (isset($courseid) && $courseid != $id) {
                continue;
            }
            if (isset($lrs) && $lrs != $config->lrs) {
                continue;
            }
            self::scan_course_logs($id, $config);
        }
    }

    /**
     * Retry failed events.
     *
     * @param int $lrs
     * @param int $courseid
     * @return void
     */
    public static function retry(int $lrs, int $courseid = null) {
        global $DB;

        $firstid = 0;
        if (!$lastlog = errors::get_event_last_log($lrs, $courseid)) {
            return;
        }
        $lastid = $lastlog->id;

        while (1) {
            $logs = errors::get_event_logs_batch($lrs, $courseid, $firstid, $lastid);

            // No more errors. Exit.
            if (empty($logs)) {
                return;
            }

            // Convert the events.
            foreach ($logs as $log) {
                $data = json_decode($log->data);

                $transaction = $DB->start_delegated_transaction();

                errors::delete_log($log->id);
                $statements = converter::convert_events([$data->source], $log->lrs, $log->courseid);
                client::queue($log->lrs, $log->courseid, $statements);

                $transaction->allow_commit();
            }

            $firstid = end($logs)->id;
        }        
    }

    /**
     * Scan the logs of a given course.
     *
     * @param int $courseid
     * @param object $config
     * @param object $status
     * @return void
     */
    protected static function scan_course_logs(int $courseid, object $config, object $status = null) {
        global $DB;

        // Get the status.
        if (!isset($status)) {
            if (!$status = $DB->get_record('block_trax_xapi_logs_status', ['courseid' => $courseid, 'lrs' => $config->lrs])) {
                $status = (object)[
                    'courseid' => $courseid,
                    'lrs' => $config->lrs,
                    'lastevent' => 0,
                    'timestamp' => time(),
                ];
                $status->id = $DB->insert_record('block_trax_xapi_logs_status', $status, true);
            }
        }

        // Get a batch of logs/events.
        $sql = "
            SELECT *
            FROM {logstore_standard_log}
            WHERE courseid = ? AND id > ? AND timecreated >= ?
            ORDER BY id
        ";
        $dateobj = \DateTime::createFromFormat('d/m/Y', $config->logs_from);
        $from = strtotime($dateobj->format('d-m-Y'));
        $events = $DB->get_records_sql($sql, [$courseid, $status->lastevent, $from], 0, 100);

        // No more event for this couorse.
        if (count($events) == 0) {
            return;
        }

        // Filter events again because the selector conditions may not be enough.
        $filtered_events = array_filter($events, function ($event) {
            return selector::should_track($event);
        });

        // No more filtered event for this couorse.
        if (count($filtered_events) == 0) {
            return;
        }

        $transaction = $DB->start_delegated_transaction();

        // Convert the events.
        $statements = converter::convert_events($filtered_events, $config->lrs, $courseid);

        // Send the statements.
        client::queue($config->lrs, $courseid, $statements);

        // Update the status.
        $last_event = end($events);
        $status->lastevent = $last_event->id;
        $status->timestamp = time();
        $DB->update_record('block_trax_xapi_logs_status', $status);

        $transaction->allow_commit();

        // Continue: we got some events so they may be others to process.
        // The statements list may have been empty due to modeler skippings.
        self::scan_course_logs($courseid, $config, $status);
    }
}
