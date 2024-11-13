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

use context_course;

class config {

    /**
     * No LRS.
     */
    const LRS_NO = 0;

    /**
     * Production LRS.
     */
    const LRS_PRODUCTION = 1;

    /**
     * Test LRS.
     */
    const LRS_TEST = 2;

    /**
     * No event catched.
     */
    const EVENTS_MODE_NO = 0;

    /**
     * Events catched in real time.
     */
    const EVENTS_MODE_LIVE = 1;

    /**
     * Events catched from the log store.
     */
    const EVENTS_MODE_LOGS = 2;

    /**
     * Actors identification: username.
     */
    const ACTORS_ID_USERNAME = 1;

    /**
     * Actors identification: id.
     */
    const ACTORS_ID_DBID = 2;

    /**
     * Actors identification: UUID.
     */
    const ACTORS_ID_UUID = 3;

    /**
     * Actors identification: email.
     */
    const ACTORS_ID_EMAIL = 4;

    /**
     * Get the production LRS endpoint without trailing slash.
     *
     * @return string
     */
    public static function lrs_endpoint() {
        return rtrim(
            get_config('block_trax_xapi_agent', 'lrs_endpoint'),
            "/ \n\r\t\v\x00"
        );
    }

    /**
     * Get the production LRS username.
     *
     * @return string
     */
    public static function lrs_username() {
        return trim(get_config('block_trax_xapi_agent', 'lrs_username'));
    }

    /**
     * Get the production LRS password.
     *
     * @return string
     */
    public static function lrs_password() {
        return trim(get_config('block_trax_xapi_agent', 'lrs_password'));
    }

    /**
     * Get the test LRS endpoint without trailing slash.
     *
     * @return string
     */
    public static function lrs2_endpoint() {
        return rtrim(
            get_config('block_trax_xapi_agent', 'lrs2_endpoint'),
            "/ \n\r\t\v\x00"
        );
    }

    /**
     * Get the test LRS username.
     *
     * @return string
     */
    public static function lrs2_username() {
        return trim(get_config('block_trax_xapi_agent', 'lrs2_username'));
    }

    /**
     * Get the test LRS password.
     *
     * @return string
     */
    public static function lrs2_password() {
        return trim(get_config('block_trax_xapi_agent', 'lrs2_password'));
    }

    /**
     * Get the LRS options.
     *
     * @return array
     */
    public static function lrs_options() {
        $targets = [
            self::LRS_NO => get_string('lrs_no', 'block_trax_xapi_agent'),
        ];
        if (!empty(self::lrs_endpoint())) {
            $targets[self::LRS_PRODUCTION] = get_string('lrs_production', 'block_trax_xapi_agent');
        }
        if (!empty(self::lrs2_endpoint())) {
            $targets[self::LRS_TEST] = get_string('lrs_test', 'block_trax_xapi_agent');
        }
        return $targets;
    }

    /**
     * Get the LRS config.
     *
     * @param int $lrsnum
     * @return object|false
     */
    public static function lrs_config(int $lrsnum) {
        if ($lrsnum == self::LRS_NO) {
            return false;
        }

        if ($lrsnum == self::LRS_PRODUCTION) {
            $config = (object)[
                'endpoint' => self::lrs_endpoint(),
                'username' => self::lrs_username(),
                'password' => self::lrs_password(),
            ];
        }

        if ($lrsnum == self::LRS_TEST) {
            $config = (object)[
                'endpoint' => self::lrs2_endpoint(),
                'username' => self::lrs2_username(),
                'password' => self::lrs2_password(),
            ];
        }

        // Add a trailing slash to the endpoint.
        $config->endpoint .= '/';

        return $config;
    }

    /**
     * Is an LRS configured?
     *
     * @param int $lrsnum
     * @return bool
     */
    public static function lrs_configured(int $lrsnum) {
        if ($config = self::lrs_config($lrsnum)) {
            return !empty($config->endpoint);
        }
        return false;
    }

    /**
     * Get the identification modes.
     *
     * @return array
     */
    public static function actors_identification_modes() {
        return [
            self::ACTORS_ID_USERNAME => get_string('actors_id_username', 'block_trax_xapi_agent'),
            self::ACTORS_ID_DBID => get_string('actors_id_dbid', 'block_trax_xapi_agent'),
            self::ACTORS_ID_UUID => get_string('actors_id_uuid', 'block_trax_xapi_agent'),
            self::ACTORS_ID_EMAIL => get_string('actors_id_email', 'block_trax_xapi_agent'),
        ];
    }

    /**
     * Is actor identification based on username?
     *
     * @return bool
     */
    public static function actors_id_with_username() {
        return get_config('block_trax_xapi_agent', 'actors_id_mode') == self::ACTORS_ID_USERNAME;
    }

    /**
     * Is actor identification based on database id?
     *
     * @return bool
     */
    public static function actors_id_with_dbid() {
        return get_config('block_trax_xapi_agent', 'actors_id_mode') == self::ACTORS_ID_DBID;
    }

    /**
     * Is actor identification based on an UUID?
     *
     * @return bool
     */
    public static function actors_id_with_uuid() {
        return get_config('block_trax_xapi_agent', 'actors_id_mode') == self::ACTORS_ID_UUID;
    }

    /**
     * Is actor identification based on email?
     *
     * @return bool
     */
    public static function actors_id_with_email() {
        return get_config('block_trax_xapi_agent', 'actors_id_mode') == self::ACTORS_ID_EMAIL;
    }

    /**
     * Is actor identification based on a custom field?
     *
     * @return bool
     */
    public static function actors_id_with_custom_field() {
        return !empty(
            get_config('block_trax_xapi_agent', 'actors_id_custom_field')
        );
    }

    /**
     * Get the actor id custom field.
     *
     * @return string
     */
    public static function actors_id_custom_field() {
        return get_config('block_trax_xapi_agent', 'actors_id_custom_field');
    }

    /**
     * Does the actor format include the name?
     *
     * @return bool
     */
    public static function actors_id_includes_name() {
        return get_config('block_trax_xapi_agent', 'actors_id_include_name') == 1;
    }

    /**
     * Get the actor id homepage.
     *
     * @param string $mode
     * @return string
     */
    public static function actors_id_homepage(string $mode) {
        return rtrim(
            get_config('block_trax_xapi_agent', 'actors_id_homepage'),
            "/ \n\r\t\v\x00"
        ) . '/' . $mode;
    }

    /**
     * Get the actor id homepage without trailing slash.
     *
     * @return string
     */
    public static function activities_id_base() {
        return rtrim(
            get_config('block_trax_xapi_agent', 'activities_id_base'),
            "/ \n\r\t\v\x00"
        );
    }

    /**
     * Should we track the following event category?
     *
     * @param string $name
     * @return bool
     */
    public static function track_moodle_event(string $name) {
        return get_config('block_trax_xapi_agent', "moodle_events_$name");
    }

    /**
     * Get the custom templates folder as an absolute path with a trailing slash.
     *
     * @return string|false
     */
    public static function custom_templates_folder() {
        global $CFG;

        // Remove leading and trailing slashes and back-slashes.
        $res = trim(
            get_config('block_trax_xapi_agent', 'custom_templates_folder'),
            "\\/ \n\r\t\v\x00"
        );
        // Return false if empty.
        if (empty($res)) {
            return false;
        }
        // Replace back-slashed by slashes.
        $res = str_replace('\\', '/', $res);
        // Add the absolute root and a trailing slash.
        return $CFG->dirroot . "/$res/";
    }

    /**
     * Get the custom modelers namespace with leading back-slash.
     *
     * @return string|false
     */
    public static function custom_modelers_namespace() {
        // Remove leading and trailing slashes and back-slashes.
        $res = trim(
            get_config('block_trax_xapi_agent', 'custom_modelers_namespace'),
            "\\/ \n\r\t\v\x00"
        );
        // Return false if empty.
        if (empty($res)) {
            return false;
        }
        // Replace slashed by back-slashes.
        $res = str_replace('/', '\\', $res);
        // Add a leading back-slashes.
        return "\\$res";
    }

    /**
     * Get the events mode options.
     *
     * @return array
     */
    public static function events_mode_options() {
        return [
            self::EVENTS_MODE_NO => get_string('events_mode_no', 'block_trax_xapi_agent'),
            self::EVENTS_MODE_LIVE => get_string('events_mode_live', 'block_trax_xapi_agent'),
            self::EVENTS_MODE_LOGS => get_string('events_mode_logs', 'block_trax_xapi_agent'),
        ];
    }

    /**
     * Get a course config.
     *
     * @param int $courseid
     * @return false|object
     */
    public static function course_config($courseid) {
        global $DB;

        $course_context = context_course::instance($courseid);
        $block_instance_record = $DB->get_record('block_instances', ['blockname' => 'trax_xapi_agent', 'parentcontextid' => $course_context->id]);

        return $block_instance_record === false
            ? false
            : block_instance('trax_xapi_agent', $block_instance_record)->config;
    }

    /**
     * Get the index of course configs for live events.
     *
     * @return array
     */
    public static function live_event_course_configs() {
        return self::course_configs(self::EVENTS_MODE_LIVE);
    }

    /**
     * Get the index of course configs for log store.
     *
     * @return array
     */
    public static function log_store_course_configs() {
        return self::course_configs(self::EVENTS_MODE_LOGS);
    }

    /**
     * Get the index of course configs for a given event mode.
     *
     * @param int $event_mode
     * @return array
     */
    public static function course_configs(int $event_mode) {
        global $DB;
        
        // Get all the TRAX xAPI blocks and there configs.
        $block_instance_records = $DB->get_records('block_instances', ['blockname' => 'trax_xapi_agent']);

        // Extract all the configs indexed by their context IDs.
        $configs_by_context_ids = [];
        foreach ($block_instance_records as $block_instance_record) {
            $config = block_instance('trax_xapi_agent', $block_instance_record)->config;
            if (!empty($config->lrs)
                && self::lrs_configured($config->lrs)
                && !empty($config->events_mode)
                && $config->events_mode == $event_mode
            ) {
                $configs_by_context_ids[$block_instance_record->parentcontextid] = $config;
            }
        }

        // Get all the contexts.
        $contexts = $DB->get_records_list('context', 'id', array_keys($configs_by_context_ids));

        // Now, fill the course configs.
        $course_configs = [];
        foreach ($contexts as $context) {
            $course_configs[$context->instanceid] = $configs_by_context_ids[$context->id];
        }

        return $course_configs;
    }
}


