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

use block_trax_xapi\exceptions\client_exception;

class client {

    /**
     * Add a list of statements to the queue.
     *
     * @param int $lrsnum
     * @param array $statements
     * @return void
     * @throws \block_trax_xapi\exceptions\client_exception
     */
    public static function send(int $lrsnum, array $statements) {
        $lrs = new lrs($lrsnum);

        // Post the statements.
        try {
            $response = $lrs->statements()->post($statements);
        } catch (\Exception $e) {
            // Error from the HTTP client.
            logger::log_http_error($lrsnum, $lrs->endpoint(), 'statements', 'post', $statements, $lrs->headers(), $e);
            throw new client_exception();
        }

        // Error returned from the LRS.
        if ($response->code >= 400) {
            logger::log_lrs_error($lrsnum, 'statements', 'post', $statements, $response);
            throw new client_exception();
        }
    }

    /**
     * Flush the queue.
     *
     * @param int $lrsnum
     * @return void
     * @throws \block_trax_xapi\exceptions\client_exception
     */
    public static function flush($lrsnum) {
    }
}
