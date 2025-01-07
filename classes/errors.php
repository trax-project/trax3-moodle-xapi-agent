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

class errors {

    /**
     * Modeling error.
     */
    const ERROR_MODELING = 1;

    /**
     * HTTP error.
     */
    const ERROR_CLIENT = 2;

    /**
     * LRS error.
     */
    const ERROR_LRS = 3;

    /**
     * Log a modeling error.
     *
     * @param int $source
     * @param int $lrsnum
     * @param int $courseid
     * @param \core\event\base|object $sourcedata
     * @param mixed $optsourcedata
     * @param string $eventname
     * @param string $template
     * @param int $error
     * @param \Exception $e
     * @return void
     */
    public static function log_modeling_error(int $source, int $lrsnum, int $courseid, object $sourcedata, mixed $optsourcedata, string $eventname, string $template, int $error, \Exception $exception = null) {
        global $DB;

        $sourcedata = get_class($sourcedata) == 'stdClass' ? $sourcedata : $sourcedata->get_data();
        
        $DB->insert_record('block_trax_xapi_errors', [
            'lrs' => $lrsnum,
            'type' => self::ERROR_MODELING,
            'error' => $error,
            'source' => $source,
            'data' => json_encode([
                'source' => $sourcedata,
                'optsource' => $optsourcedata,
                'eventname' => $eventname,
                'template' => $template,
                'exception' => $exception,
            ]),
            'courseid' => $courseid,
            'timestamp' => time(),
        ]);
    }

    /**
     * Log an HTTP error.
     *
     * @param int $lrsnum
     * @param string $endpoint
     * @param string $api
     * @param string $method
     * @param mixed $data
     * @param array $headers
     * @param \Exception $e
     * @return void
     */
    public static function log_lrs_client_error(int $lrsnum, string $endpoint, string $api, string $method, mixed $data, array $headers, \Exception $exception = null) {
        global $DB;
        $DB->insert_record('block_trax_xapi_errors', [
            'lrs' => $lrsnum,
            'type' => self::ERROR_CLIENT,
            'error' => 0,
            'source' => null,
            'data' => json_encode([
                'endpoint' => $endpoint,
                'api' => $api,
                'method' => $method,
                'data' => $data,
                'headers' => $headers,
                'exception' => $exception,
            ]),
            'courseid' => null,
            'timestamp' => time(),
        ]);
    }

    /**
     * Log an LRS error.
     *
     * @param int $lrsnum
     * @param string $endpoint
     * @param string $api
     * @param string $method
     * @param mixed $data
     * @param object $client_response
     * @return void
     */
    public static function log_lrs_response_error(int $lrsnum, string $endpoint, string $api, string $method, mixed $data, object $client_response) {
        global $DB;
        $DB->insert_record('block_trax_xapi_errors', [
            'lrs' => $lrsnum,
            'type' => self::ERROR_LRS,
            'error' => $client_response->code,
            'source' => null,
            'data' => json_encode([
                'endpoint' => $endpoint,
                'api' => $api,
                'method' => $method,
                'data' => $data,
                'response' => $client_response,
            ]),
            'courseid' => null,
            'timestamp' => time(),
        ]);
    }

    /**
     * Count event modeling errors.
     *
     * @param int $lrsnum
     * @param int $courseid
     * @return int
     */
    public static function count_event_modeling_errors(int $lrsnum, int $courseid = null) {
        global $DB;
        if (isset($courseid)) {
            return $DB->count_records('block_trax_xapi_errors', ['source' => converter::SOURCE_EVENT, 'courseid' => $courseid, 'lrs' => $lrsnum, 'type' => self::ERROR_MODELING]);
        } else {
            return $DB->count_records('block_trax_xapi_errors', ['source' => converter::SOURCE_EVENT, 'lrs' => $lrsnum, 'type' => self::ERROR_MODELING]);
        }
    }

    /**
     * Count scorm modeling errors.
     *
     * @param int $lrsnum
     * @param int $courseid
     * @return int
     */
    public static function count_scorm_modeling_errors(int $lrsnum, int $courseid = null) {
        global $DB;
        if (isset($courseid)) {
            return $DB->count_records('block_trax_xapi_errors', ['source' => converter::SOURCE_SCORM, 'courseid' => $courseid, 'lrs' => $lrsnum, 'type' => self::ERROR_MODELING]);
        } else {
            return $DB->count_records('block_trax_xapi_errors', ['source' => converter::SOURCE_SCORM, 'lrs' => $lrsnum, 'type' => self::ERROR_MODELING]);
        }
    }

    /**
     * Count client errors.
     *
     * @param int $lrsnum
     * @return int
     */
    public static function count_client_errors(int $lrsnum) {
        global $DB;
        return $DB->count_records('block_trax_xapi_errors', ['courseid' => null, 'lrs' => $lrsnum]);
    }

    /**
     * Delete errors.
     *
     * @param int $lrsnum
     * @param int $courseid
     * @return void
     */
    public static function delete_event_logs(int $lrsnum, int $courseid = null) {
        global $DB;
        if (isset($courseid)) {
            return $DB->delete_records('block_trax_xapi_errors', ['source' => converter::SOURCE_EVENT, 'courseid' => $courseid, 'lrs' => $lrsnum]);
        } else {
            return $DB->delete_records('block_trax_xapi_errors', ['source' => converter::SOURCE_EVENT, 'lrs' => $lrsnum]);
        }
    }

    /**
     * Delete errors.
     *
     * @param int $lrsnum
     * @param int $courseid
     * @return void
     */
    public static function delete_scorm_logs(int $lrsnum, int $courseid = null) {
        global $DB;
        if (isset($courseid)) {
            return $DB->delete_records('block_trax_xapi_errors', ['source' => converter::SOURCE_SCORM, 'courseid' => $courseid, 'lrs' => $lrsnum]);
        } else {
            return $DB->delete_records('block_trax_xapi_errors', ['source' => converter::SOURCE_SCORM, 'lrs' => $lrsnum]);
        }
    }

    /**
     * Delete errors.
     *
     * @param int $lrsnum
     * @return void
     */
    public static function delete_client_logs(int $lrsnum) {
        global $DB;
        $DB->delete_records('block_trax_xapi_errors', [
            'courseid' => null,
            'lrs' => $lrsnum,
        ]);
    }

    /**
     * Delete errors.
     *
     * @param int $id
     * @return void
     */
    public static function delete_log(int $id) {
        global $DB;
        $DB->delete_records('block_trax_xapi_errors', [
            'id' => $id,
        ]);
    }

    /**
     * Get errors.
     *
     * @param int $lrsnum
     * @param int $courseid
     * @return array
     */
    public static function get_event_logs(int $lrsnum, int $courseid = null) {
        return self::get_logs(converter::SOURCE_EVENT, $lrsnum, $courseid);
    }

    /**
     * Get errors.
     *
     * @param int $lrsnum
     * @param int $courseid
     * @return array
     */
    public static function get_scorm_logs(int $lrsnum, int $courseid = null) {
        return self::get_logs(converter::SOURCE_SCORM, $lrsnum, $courseid);
    }

    /**
     * Get errors.
     *
     * @param int $lrsnum
     * @return array
     */
    public static function get_client_logs(int $lrsnum) {
        global $DB;
        return array_reverse(
            $DB->get_records('block_trax_xapi_errors', ['courseid' => null, 'lrs' => $lrsnum])
        );
    }

    /**
     * Get errors.
     *
     * @param int $lrsnum
     * @param int $courseid
     * @return object|false
     */
    public static function get_event_last_log(int $lrsnum, int $courseid = null) {
        return self::get_last_log(converter::SOURCE_EVENT, $lrsnum, $courseid);
    }

    /**
     * Get errors.
     *
     * @param int $lrsnum
     * @param int $courseid
     * @return object|false
     */
    public static function get_scorm_last_log(int $lrsnum, int $courseid = null) {
        return self::get_last_log(converter::SOURCE_SCORM, $lrsnum, $courseid);
    }

    /**
     * Get errors.
     *
     * @param int $lrsnum
     * @param int $courseid
     * @param int $fromid
     * @param int $toid
     * @return array
     */
    public static function get_event_logs_batch(int $lrsnum, int $courseid = null, int $fromid = 0, int $toid = 0) {
        return self::get_logs_batch(converter::SOURCE_EVENT, $lrsnum, $courseid, $fromid, $toid);
    }

    /**
     * Get errors.
     *
     * @param int $lrsnum
     * @param int $courseid
     * @param int $fromid
     * @param int $toid
     * @return array
     */
    public static function get_scorm_logs_batch(int $lrsnum, int $courseid = null, int $fromid = 0, int $toid = 0) {
        return self::get_logs_batch(converter::SOURCE_SCORM, $lrsnum, $courseid, $fromid, $toid);
    }

    /**
     * Get errors.
     *
     * @param int $source
     * @param int $lrsnum
     * @param int $courseid
     * @return array
     */
    protected static function get_logs(int $source, int $lrsnum, int $courseid = null) {
        global $DB;
        if (isset($courseid)) {
            return $DB->get_records_sql("
                SELECT error.*
                FROM {block_trax_xapi_errors} error
                WHERE error.source = :source
                    AND error.lrs = :lrs
                    AND error.courseid = :courseid
                ORDER BY error.id DESC
            ", [
                'source' => $source,
                'lrs' => $lrsnum,
                'courseid' => $courseid,
            ]);

        } else {
            return $DB->get_records_sql("
                SELECT error.*, course.id AS courseid, course.fullname AS coursename
                FROM {block_trax_xapi_errors} error
                JOIN {course} course ON error.courseid = course.id
                WHERE error.source = :source
                    AND error.lrs = :lrs
                ORDER BY error.id DESC
            ", [
                'source' => $source,
                'lrs' => $lrsnum,
            ]);
        }
    }

    /**
     * Get errors.
     *
     * @param int $source
     * @param int $lrsnum
     * @param int $courseid
     * @return object|false
     */
    protected static function get_last_log(int $source, int $lrsnum, int $courseid = null) {
        global $DB;
        if (isset($courseid)) {
            $logs = $DB->get_records_sql("
                SELECT error.*
                FROM {block_trax_xapi_errors} error
                WHERE error.source = :source
                    AND error.lrs = :lrs
                    AND error.courseid = :courseid
                ORDER BY error.id DESC
            ", [
                'source' => $source,
                'lrs' => $lrsnum,
                'courseid' => $courseid,
            ], 0, 1);
        } else {
            $logs = $DB->get_records_sql("
                SELECT error.*
                FROM {block_trax_xapi_errors} error
                WHERE error.source = :source
                    AND error.lrs = :lrs
                ORDER BY error.id DESC
            ", [
                'source' => $source,
                'lrs' => $lrsnum,
            ], 0, 1);
        }
        if (empty($logs)) {
            return false;
        }
        return end($logs);
    }

    /**
     * Get errors.
     *
     * @param int $source
     * @param int $lrsnum
     * @param int $courseid
     * @param int $fromid
     * @param int $toid
     * @return array
     */
    protected static function get_logs_batch(int $source, int $lrsnum, int $courseid = null, int $fromid = 0, int $toid = 0) {
        global $DB;
        if (isset($courseid)) {
            return $DB->get_records_sql("
                SELECT error.*
                FROM {block_trax_xapi_errors} error
                WHERE error.source = :source
                    AND error.lrs = :lrs
                    AND error.courseid = :courseid
                    AND error.id > :fromid
                    AND error.id <= :toid
                ORDER BY error.id ASC
            ", [
                'source' => $source,
                'lrs' => $lrsnum,
                'courseid' => $courseid,
                'fromid' => $fromid,
                'toid' => $toid,
            ], 0, 100);
        } else {
            return $DB->get_records_sql("
                SELECT error.*
                FROM {block_trax_xapi_errors} error
                WHERE error.source = :source
                    AND error.lrs = :lrs
                    AND error.id > :fromid
                    AND error.id <= :toid
                ORDER BY error.id ASC
            ", [
                'source' => $source,
                'lrs' => $lrsnum,
                'fromid' => $fromid,
                'toid' => $toid,
            ], 0, 100);
        }
    }
}
