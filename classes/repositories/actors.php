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

use block_trax_xapi\config;
use core_user;

require_once($CFG->dirroot.'/user/profile/lib.php');

class actors extends repository {

    /**
     * DB table.
     *
     * @var string $table
     */
    protected $table = 'block_trax_xapi_actors';

    /**
     * Get a user, given a Moodle ID.
     *
     * @param int $mid Moodle ID of the cohort
     * @return array
     */
    public function get_user(int $mid = 0) {
        $user = core_user::get_user($mid, '*', MUST_EXIST);
        $xapi = null;
        
        if (config::actors_id_with_custom_field()) {
            profile_load_custom_fields($user);
            $fieldName = config::actors_id_custom_field();
            if (!empty($user->profile[$fieldName])) {
                $xapi = [
                    'objectType' => 'Agent',
                    'account' => [
                        'name' => $user->profile[$fieldName],
                        'homePage' => config::actors_id_homepage($fieldName),
                    ]
                ];
            };
        }
        
        if (is_null($xapi) && config::actors_id_with_username()) {
            $xapi = [
                'objectType' => 'Agent',
                'account' => [
                    'name' => $user->username,
                    'homePage' => config::actors_id_homepage('username'),
                ]
            ];
        }
        
        if (is_null($xapi) && config::actors_id_with_dbid()) {
            $xapi = [
                'objectType' => 'Agent',
                'account' => [
                    'name' => $user->id,
                    'homePage' => config::actors_id_homepage('dbid'),
                ]
            ];
        }
        
        if (is_null($xapi) && config::actors_id_with_uuid()) {
            $entry = $this->get_or_create_db_entry($mid, 'user');
            $xapi = [
                'objectType' => 'Agent',
                'account' => [
                    'name' => $entry->uuid,
                    'homePage' => config::actors_id_homepage('uuid'),
                ]
            ];
        }

        if (is_null($xapi) && config::actors_id_with_email()) {
            $xapi = [
                'objectType' => 'Agent',
                'mbox' => 'mailto:' . $user->email,
            ];
        }

        if (config::actors_id_includes_name()) {
            $xapi['name'] = $user->firstname . ' ' . $user->lastname;
        }

        return $xapi;
    }
}
