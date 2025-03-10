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

use block_trax_xapi\modelers\base as modeler;
use block_trax_xapi\utils;
use block_trax_xapi\repositories\repo;
use block_trax_xapi\exceptions\ignore_event_exception;

abstract class base extends modeler {

    /**
     * @var object $attempt
     */
    protected $attempt;

    /**
     * Course.
     *
     * @var object
     */
    protected $course;

    /**
     * SCORM module props.
     *
     * @var object
     */
    protected $scorm_props;

    /**
     * SCO props.
     *
     * @var object
     */
    protected $sco_props;

    /**
     * Get an xAPI statement, given a SCORM attempt.
     *
     * @param object $attempt
     * @param mixed $optdata
     * @return object
     */
    public function statement($attempt, $optdata = null) {
        $this->attempt = $attempt;

        if (isset($this->attempt->values) && !is_array($this->attempt->values)) {
            $this->attempt->values = (array)$this->attempt->values;
        }

        // Get the right template.
        if (!$template = $this->template()) {
            return (object)['error' => self::ERROR_IGNORE, 'source' => $attempt, 'optsource' => $optdata, 'template' => null];
        }

        // Open the template.
        if (!$content = $this->templateContents($template)) {
            return (object)['error' => self::ERROR_TEMPLATE_FILE, 'source' => $attempt, 'optsource' => $optdata, 'template' => $template];
        }

        // Parse the JSON.
        if (!$json = json_decode($content, true)) {
            return (object)['error' => self::ERROR_TEMPLATE_JSON, 'source' => $attempt, 'optsource' => $optdata, 'template' => $template];
        }

        // Fill placeholders.
        try {
            $statement = $this->fill_placeholders($json);
        } catch (ignore_event_exception $e) {
            return (object)['error' => self::ERROR_IGNORE, 'source' => $attempt, 'optsource' => $optdata, 'template' => $template];
        } catch (\Exception $e) {
            return (object)['error' => self::ERROR_PLACEHOLDER, 'source' => $attempt, 'optsource' => $optdata, 'template' => $template, 'exception' => $e];
        }

        return (object)['error' => self::ERROR_NO, 'source' => $attempt, 'optsource' => $optdata, 'template' => $template, 'statement' => $statement];
    }

    /**
     * @return array
     */
    protected function user() {
        if (!isset($this->user_xapi)) {
            $this->user_xapi = repo::actors()->get_user($this->attempt->userid);
        }
        return $this->user_xapi;
    }

    /**
     * @return void
     */
    protected function load_course() {
        $this->load_attempt();
    }

    /**
     * @return void
     */
    protected function load_attempt() {
        if (!isset($this->course)) {
            $this->course = repo::activities()->get_course($this->attempt->courseid);
            $this->course_props = repo::activities()->get_course_props($this->attempt->courseid, $this->course);
        }
        if (!isset($this->scorm_props)) {
            $this->scorm_props = repo::activities()->get_scorm_props($this->attempt->scormid, $this->course);
        }
        if (!isset($this->sco_props)) {
            $this->sco_props = repo::activities()->get_sco_props($this->attempt->scoid, $this->course);
        }
    }

    /**
     * @return string
     */
    protected function scorm_iri() {
        $this->load_attempt();
        return $this->scorm_props->iri;
    }

    /**
     * @return string
     */
    protected function scorm_name() {
        $this->load_attempt();
        return $this->scorm_props->name;
    }

    /**
     * @return string
     */
    protected function scorm_component() {
        $this->load_attempt();
        return $this->scorm_props->component;
    }

    /**
     * @return string
     */
    protected function scorm_url() {
        $this->load_attempt();
        return $this->scorm_props->url;
    }

    /**
     * @return string
     */
    protected function scorm_idnumber() {
        $this->load_attempt();
        return $this->scorm_props->idnumber;
    }

    /**
     * @return string
     */
    protected function sco_iri() {
        $this->load_attempt();
        return $this->sco_props->iri;
    }

    /**
     * @return string
     */
    protected function sco_name() {
        $this->load_attempt();
        return $this->sco_props->name;
    }

    /**
     * @return string
     */
    protected function timestamp() {
        return utils::timestamp($this->attempt->timestamp);
    }
    
    /**
     * @return string|null
     */
    protected function success() {
        return (isset($this->attempt->values['cmi.success_status']) && $this->attempt->values['cmi.success_status'] == 'passed')    // SCORM 2004
            || (isset($this->attempt->values['cmi.core.lesson_status']) && $this->attempt->values['cmi.core.lesson_status'] == 'passed');     // SCORM 1.2
    }
    
    /**
     * @return string|null
     */
    protected function score() {
        if (!isset($this->attempt->values['cmi.score.raw'])
            && !isset($this->attempt->values['cmi.score.scaled'])
            && !isset($this->attempt->values['cmi.core.score.raw'])
            && !isset($this->attempt->values['cmi.core.score.scaled'])
        ) {
            return null;
        }
        $score = [];
        // SCORM 2004.
        if (isset($this->attempt->values['cmi.score.raw'])) {
            $score['raw'] = (int) $this->attempt->values['cmi.score.raw'];
        }
        if (isset($this->attempt->values['cmi.score.min'])) {
            $score['min'] = (int) $this->attempt->values['cmi.score.min'];
        }
        if (isset($this->attempt->values['cmi.score.max'])) {
            $score['max'] = (int) $this->attempt->values['cmi.score.max'];
        }
        if (isset($this->attempt->values['cmi.score.scaled'])) {
            $score['scaled'] = (float) $this->attempt->values['cmi.score.scaled'];
        }
        // SCORM 1.2.
        if (isset($this->attempt->values['cmi.core.score.raw'])) {
            $score['raw'] = (int) $this->attempt->values['cmi.core.score.raw'];
        }
        if (isset($this->attempt->values['cmi.core.score.min'])) {
            $score['min'] = (int) $this->attempt->values['cmi.core.score.min'];
        }
        if (isset($this->attempt->values['cmi.core.score.max'])) {
            $score['max'] = (int) $this->attempt->values['cmi.core.score.max'];
        }
        if (isset($this->attempt->values['cmi.core.score.scaled'])) {
            $score['scaled'] = (float) $this->attempt->values['cmi.core.score.scaled'];
        }
        return $score;
    }
    
    /**
     * @return string|null
     */
    protected function duration() {
        // SCORM 2004.
        if (isset($this->attempt->values['cmi.total_time'])) {
            return $this->attempt->values['cmi.total_time'];
        }
        // SCORM 1.2.
        if (isset($this->attempt->values['cmi.core.total_time'])) {
            return utils::iso8601_duration_from_scorm12($this->attempt->values['cmi.core.total_time']);
        }
        return null;
    }
}
