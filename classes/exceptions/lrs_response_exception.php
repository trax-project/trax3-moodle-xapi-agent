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

namespace block_trax_xapi\exceptions;

defined('MOODLE_INTERNAL') || die();

use Exception;

class lrs_response_exception extends Exception {

    /**
     * @var int
     */
    public $lrsnum;
    
    /**
     * @var string
     */
    public $endpoint;
    
    /**
     * @var string
     */
    public $api;
    
    /**
     * @var string
     */
    public $method;
    
    /**
     * @var mixed
     */
    public $data;
    
    /**
     * @var object
     */
    public $client_response;
    
    /**
     * @param int $lrsnum
     * @param string $endpoint
     * @param string $api
     * @param string $method
     * @param mixed $data
     * @param object $client_response
     * @return void
     */
    public function __construct(int $lrsnum, string $endpoint, string $api, string $method, mixed $data, object $client_response) {
        parent::__construct('LRS response exception');
        $this->lrsnum = $lrsnum;
        $this->endpoint = $endpoint;
        $this->api = $api;
        $this->method = $method;
        $this->data = $data;
        $this->client_response = $client_response;
    }
}
