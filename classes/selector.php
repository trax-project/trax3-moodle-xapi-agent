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
 * @package    block_trax_xapi_agent
 * @copyright  2024 Sébastien Fraysse <sebastien@fraysse.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_trax_xapi_agent;

defined('MOODLE_INTERNAL') || die();

class selector {

    /**
     * Should we track the following event?
     *
     * @param \core\event\base|object $event
     * @return bool
     */
    public static function should_track($event) {
        if (str_contains($event->eventname, 'course_module_viewed')) {
            return config::track_moodle_event('navigation');
        }

        foreach (config::supported_events(true) as $domain => $events) {
            if (in_array($event->eventname, $events)) {
                return config::track_moodle_event($domain);
            }
        }

        return false;
    }
}


