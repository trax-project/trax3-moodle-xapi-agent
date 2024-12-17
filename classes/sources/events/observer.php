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

namespace block_trax_xapi\sources\events;

defined('MOODLE_INTERNAL') || die();

use block_trax_xapi\config;
use block_trax_xapi\selector;
use block_trax_xapi\converter;
use block_trax_xapi\client;
use block_trax_xapi\exceptions\client_exception;

class observer {

    /**
     * Catch events.
     *
     * @param \core\event\base $event
     * @return void
     */
    public static function catch(\core\event\base $event) {

        if ($event->courseid) {
            // Course events.
            $configs = config::live_event_course_configs();
            if (!in_array($event->courseid, array_keys($configs))) {
                return;
            }
            $config = $configs[$event->courseid];

        } else {
            // System level events.
            if (!config::live_system_events_enabled()) {
                return;
            }
            $config = config::system_events_config();
        }
        
        // Keep only supported events.
        if (!selector::should_track($event)) {
            return;
        }

        // Convert the events.
        $statements = converter::convert_events([$event], $config->lrs);

        // Send the statements.
        if (count($statements) > 0) {
            try {
                client::send($config->lrs, $statements);
                client::flush($config->lrs);
            } catch (client_exception $e) {
                return;
            }
        }
    }
}
