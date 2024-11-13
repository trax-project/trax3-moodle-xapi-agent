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

use block_trax_xapi_agent\config;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface as GuzzleResponse;

class lrs {

    /**
     * Guzzle client.
     *
     * @var GuzzleClient $guzzle
     */
    protected $guzzle;

    /**
     * LRS config.
     *
     * @var object $config
     */
    protected $config;

    /**
     * LRS endpoint.
     *
     * @var string $endpoint
     */
    protected $endpoint;


    /**
     * Constructor.
     *
     * @param int $lrsnum
     * @return void
     */
    public function __construct(int $lrsnum) {
        $this->config = config::lrs_config($lrsnum);
        $this->guzzle = new GuzzleClient();
    }

    /**
     * Get the statements API.
     *
     * @return $this
     */
    public function statements() {
        $this->endpoint = $this->config->endpoint.'statements';
        return $this;
    }

    /**
     * Get the activities state API.
     *
     * @return $this
     */
    public function states() {
        $this->endpoint = $this->config->endpoint.'activities/state';
        return $this;
    }

    /**
     * Get the activities API.
     *
     * @return $this
     */
    public function activities() {
        $this->endpoint = $this->config->endpoint.'activities';
        return $this;
    }

    /**
     * Get the agents API.
     *
     * @return $this
     */
    public function agents() {
        $this->endpoint = $this->config->endpoint.'agents';
        return $this;
    }

    /**
     * Get the activity profiles API.
     *
     * @return $this
     */
    public function activityProfiles() {
        $this->endpoint = $this->config->endpoint.'activities/profile';
        return $this;
    }

    /**
     * Get the agent profiles API.
     *
     * @return $this
     */
    public function agentProfiles() {
        $this->endpoint = $this->config->endpoint.'agents/profile';
        return $this;
    }

    /**
     * Get the about API.
     *
     * @return $this
     */
    public function about() {
        $this->endpoint = $this->config->endpoint.'about';
        return $this;
    }

    /**
     * Get the last endpoint.
     *
     * @return string
     */
    public function endpoint() {
        return $this->endpoint;
    }

    /**
     * GET xAPI data.
     */
    public function get($query = []) {
        try {
            $response = $this->guzzle->get($this->endpoint, [
                'headers' => $this->headers(),
                'query' => $query,
            ]);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
        }
        return $this->response($response);
    }

    /**
     * POST xAPI data.
     *
     * @param array $data xAPI data to be posted
     * @param array $query query string
     * @return stdClass
     */
    public function post(array $data, array $query = []) {
        try {
            $response = $this->guzzle->post($this->endpoint, [
                'headers' => $this->headers(),
                'query' => $query,
                'json' => $data,
            ]);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
        }
        return $this->response($response);
    }

    /**
     * PUT xAPI data.
     *
     * @param array $data xAPI data to be posted
     * @param array $query query string
     * @return stdClass
     */
    public function put(array $data, array $query = []) {
        try {
            $response = $this->guzzle->put($this->endpoint, [
                'headers' => $this->headers(),
                'query' => $query,
                'json' => $data,
            ]);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
        }
        return $this->response($response);
    }

    /**
     * DELETE xAPI data.
     */
    public function delete($query = []) {
        try {
            $response = $this->guzzle->delete($this->endpoint, [
                'headers' => $this->headers(),
                'query' => $query,
            ]);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
        }
        return $this->response($response);
    }

    /**
     * Returns HTTP headers.
     *
     * @return array HTTP headers
     */
    public function headers() {
        return [
            'X-Experience-API-Version' => '1.0.3',
            'Authorization' => 'Basic ' . base64_encode($this->config->username . ':' . $this->config->password),
        ];
    }

    /**
     * Returns HTTP response.
     *
     * @param GuzzleResponse $guzzleresponse Guzzle response object
     * @return stdClass Response object
     */
    protected function response($guzzleresponse) {
        if (is_null($guzzleresponse)) {
            return (object)['code' => 404];
        }

        // Code and string body.
        $res = (object)[
            'code' => $guzzleresponse->getStatusCode(),
            'content' => $guzzleresponse->getBody()->__toString(),
        ];

        // JSON body.
        $content_types = $guzzleresponse->getHeader('Content-Type');
        if (in_array('application/json', $content_types)) {
            $res->content = json_decode($guzzleresponse->getBody());
        }

        // Headers.
        $res->headers = [];
        $headers = [
            'content_type' => $content_types,
            'content_length' => $guzzleresponse->getHeader('Content-Length'),
            'xapi_version' => $guzzleresponse->getHeader('X-Experience-API-Version'),
            'xapi_consistent_through' => $guzzleresponse->getHeader('X-Experience-API-Consistent-Through'),
        ];
        foreach ($headers as $key => $header) {
            if (!empty($header)) {
                $res->headers[$key] = $header[0];
            }
        }
        $res->headers = (object)$res->headers;

        return $res;
    }
}
