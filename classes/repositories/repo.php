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

namespace block_trax_xapi\repositories;

defined('MOODLE_INTERNAL') || die();

class repo {

    private static $actors;
    private static $activities;

    /**
     * Get the actors repository.
     *
     * @return \block_trax_xapi\repositories\actors
     */
    public static function actors() {
        return !isset(self::$actors) ? self::$actors = new actors : self::$actors;
    }

    /**
     * Get the activities repository.
     *
     * @return \block_trax_xapi\repositories\activities
     */
    public static function activities() {
        return !isset(self::$activities) ? self::$activities = new activities : self::$activities;
    }
}
