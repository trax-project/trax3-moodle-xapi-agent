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

namespace block_trax_xapi\modelers\scorm;

defined('MOODLE_INTERNAL') || die();

use block_trax_xapi\utils;

class sco_interacted extends base {

    /**
     * @var object $interaction
     */
    protected $interaction;

    /**
     * Get an xAPI statement, given SCORM interaction data.
     *
     * @param object $data
     * @param mixed $optdata
     * @return object
     */
    public function statement($data, $optdata = null) {
        $this->interaction = $optdata;
        return parent::statement($data);
    }

    /**
     * Get the JSON template.
     *
     * @return string|false
     */
    protected function template() {
        return 'scorm/sco_interacted';
    }

    /**
     * @return string
     */
    protected function interaction_iri() {
        return $this->sco_iri() . '/interactions/' . $this->interaction->num;
    }

    /**
     * @return string|null
     */
    protected function interaction_description() {
        if (!isset($this->interaction->description)) {
            return null;
        }
        return utils::lang_string($this->interaction->description, $this->course);
    }

    /**
     * @return string|null
     */
    protected function interaction_type() {
        if (!isset($this->interaction->type)) {
            return null;
        }
        return $this->interaction->type;
    }

    /**
     * @return string|null
     */
    protected function interaction_response() {
        if (isset($this->interaction->learner_response)) {
            return $this->interaction->learner_response;
        }
        if (isset($this->interaction->student_response)) {
            // SCORM 1.2
            return $this->interaction->student_response;
        }
        return null;
    }

    /**
     * @return string|null
     */
    protected function interaction_success() {
        if (!isset($this->interaction->result)) {
            return null;
        }
        if ($this->interaction->result == 'correct') {
            return true;
        }
        if ($this->interaction->result == 'wrong' // SCORM 1.2
            || $this->interaction->result == 'incorrect') {
            return false;
        }
        return null;
    }

    /**
     * @return string|null
     */
    protected function interaction_duration() {
        if (!isset($this->interaction->latency)) {
            return null;
        }
        if (substr($this->interaction->latency, 0, 2) == 'PT') {
            return $this->interaction->latency;                 // SCORM 2004.
        } else {
            return utils::iso8601_duration_from_scorm12($this->interaction->latency);   // SCORM 1.2.
        }
    }

    /**
     * @return string
     */
    protected function timestamp() {
        if (isset($this->interaction->timestamp)) {
            return $this->interaction->timestamp;
        }
        if (isset($this->interaction->time)) {
            // Convert SCORM 1.2 timestamp.
            list($date, $time) = explode('T', parent::timestamp());
            return $date . 'T' . $this->interaction->time;
        }
        return parent::timestamp();
    }
}
