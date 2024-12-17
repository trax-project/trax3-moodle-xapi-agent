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

namespace block_trax_xapi\modelers;

defined('MOODLE_INTERNAL') || die();

class events {

    /**
     * Get the supported Moodle events.
     *
     * @return array
     */
    public static function list() {
        return [
            'navigation' => [
                '\core\event\course_viewed'
                //'mod_xxx_course_module_viewed', Supported programmatically
            ],
            'completion' => [
                '\core\event\course_module_completion_updated'
            ],
            'grading' => [
                '\core\event\user_graded'
            ],
            'authentication' => [
                '\core\event\user_loggedin',
                '\core\event\user_loggedout',
                '\core\event\user_loggedinas'
            ],
            'h5p' => [
                '\mod_h5pactivity\event\statement_received'
            ],
        ];
    }
}
