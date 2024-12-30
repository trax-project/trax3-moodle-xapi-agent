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

class logger {

    /**
     * Modeling error.
     */
    const ERROR_MODELING = 1;

    /**
     * HTTP error.
     */
    const ERROR_HTTP = 2;

    /**
     * LRS error.
     */
    const ERROR_LRS = 3;

    /**
     * Log a modeling error.
     *
     * @param string $type
     * @param int $lrsnum
     * @param int $courseid
     * @param \core\event\base|object $source
     * @param string $template
     * @param int $error
     * @param \Exception $e
     * @return void
     */
    public static function log_modeling_error(string $type, int $lrsnum, int $courseid, object $source, string $template, int $error, \Exception $exception = null) {
        global $DB;

        $source = get_class($source) == 'stdClass' ? $source : $source->get_data();
        
        $DB->insert_record('block_trax_xapi_errors', [
            'lrs' => $lrsnum,
            'type' => self::ERROR_MODELING,
            'error' => $error,
            'data' => json_encode([
                'type' => $type,
                'template' => $template,
                'source' => $source,
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
    public static function log_http_error(int $lrsnum, string $endpoint, string $api, string $method, mixed $data, array $headers, \Exception $exception = null) {
        global $DB;
        $DB->insert_record('block_trax_xapi_errors', [
            'lrs' => $lrsnum,
            'type' => self::ERROR_HTTP,
            'error' => 0,
            'data' => json_encode([
                'endpoint' => $endpoint,
                'api' => $api,
                'method' => $method,
                'data' => $data,
                'headers' => $headers,
                'exception' => $exception,
            ]),
            'timestamp' => time(),
        ]);
    }

    /**
     * Log an LRS error.
     *
     * @param int $lrsnum
     * @param string $api
     * @param string $method
     * @param mixed $data
     * @param object $client_response
     * @return void
     */
    public static function log_lrs_error(int $lrsnum, string $api, string $method, mixed $data, object $client_response) {
        global $DB;
        $DB->insert_record('block_trax_xapi_errors', [
            'lrs' => $lrsnum,
            'type' => self::ERROR_LRS,
            'error' => $client_response->code,
            'data' => json_encode([
                'api' => $api,
                'method' => $method,
                'data' => $data,
                'response' => $client_response,
            ]),
            'timestamp' => time(),
        ]);
    }
}


