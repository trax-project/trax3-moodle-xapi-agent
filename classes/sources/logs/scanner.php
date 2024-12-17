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
use block_trax_xapi\exceptions\client_exception;

class scanner {

    /**
     * Run the log store scanner.
     *
     * @return void
     */
    public static function run() {
        
        // First, system level events.
        $config = config::system_events_config();
        try {
            self::scan_course_logs(0, $config);
            client::flush($config->lrs);
        } catch (client_exception $e) {
            return;
        }

        // Now, course events.
        foreach (config::log_store_course_configs() as $courseid => $config) {
            try {
                self::scan_course_logs($courseid, $config);
                client::flush($config->lrs);
            } catch (client_exception $e) {
                return;
            }
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

        // Convert the events.
        $statements = converter::convert_events($filtered_events, $config->lrs);

        // Send the statements.
        if (count($statements) > 0) {
            client::send($config->lrs, $statements);
        }

        // Update the status.
        $lastEvent = end($events);
        $status->lastevent = $lastEvent->id;
        $status->timestamp = $lastEvent->timecreated;
        $DB->update_record('block_trax_xapi_logs_status', $status);

        // Continue: we got some events so they may be others to process.
        // The statements list may have been empty due to modeler skippings.
        self::scan_course_logs($courseid, $config, $status);
    }
}
