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

namespace block_trax_xapi_agent\modelers\core\event;

defined('MOODLE_INTERNAL') || die();

use block_trax_xapi_agent\modelers\base as modeler;
use block_trax_xapi_agent\exceptions\ignore_event_exception;
use block_trax_xapi_agent\repositories\repo;

require_once($CFG->dirroot . '/lib/grade/constants.php');

class user_graded extends modeler {

    /**
     * Grade item.
     *
     * @var object
     */
    protected $gradeitem;

    /**
     * Grade item props.
     *
     * @var object
     */
    protected $gradeitem_props;

    /**
     * Get the JSON template.
     *
     * @return string|false
     */
    protected function template() {
        // Be sure to be on a user grade.
        if ($this->event->userid < 0) {
            return false;
        }
        // Be sure to be on a course level grade.
        if ($this->event->contextlevel != 50) {
            return false;
        }
        // The grade has been voided.
        if (is_null($this->event_other['finalgrade'])) {
            return 'core/course_module_voided_score';
        }
        // Learner completion.
        return 'core/course_module_scored';
    }

    /**
     * @return void
     * @throws \block_trax_xapi_agent\exceptions\ignore_event_exception
     */
    protected function load_gradeitem() {
        global $DB;

        if (isset($this->gradeitem_props)) {
            return;
        }
        
        $this->gradeitem = $DB->get_record('grade_items', ['id' => $this->event_other['itemid']], '*', MUST_EXIST);

        // Check that it is an activity grade.
        if ($this->gradeitem->itemtype !== 'mod') {
            throw new ignore_event_exception('This grading event is not related to a course module.');
        }
        
        // Check that it is a value or scale grade.
        if (!in_array($this->gradeitem->gradetype, [GRADE_TYPE_SCALE, GRADE_TYPE_VALUE])) {
            throw new ignore_event_exception('This grading event is not associated with a value or scale grade.');
        }

        // Get the module instance.
        $module = $DB->get_record('modules', ['name' => $this->gradeitem->itemmodule], '*', MUST_EXIST);
        $course_module = $DB->get_record('course_modules', ['module' => $module->id, 'instance' => $this->gradeitem->iteminstance], '*', MUST_EXIST);

        $this->gradeitem_props = repo::activities()->get_course_module_props($course_module->id);
    }

    /**
     * @return string
     */
    protected function grade_iri() {
        $this->load_gradeitem();
        return $this->gradeitem_props->iri;
    }

    /**
     * @return string
     */
    protected function grade_name() {
        $this->load_gradeitem();
        return $this->gradeitem_props->name;
    }

    /**
     * @return string
     */
    protected function grade_component() {
        $this->load_gradeitem();
        return $this->gradeitem_props->component;
    }

    /**
     * @return string
     */
    protected function grade_url() {
        $this->load_gradeitem();
        return $this->gradeitem_props->url;
    }

    /**
     * @return string
     */
    protected function grade_idnumber() {
        $this->load_gradeitem();
        return $this->gradeitem_props->idnumber;
    }

    /**
     * @return array
     */
    protected function score() {
        $this->load_gradeitem();

        // Define scoring values.
        $raw = floatval($this->event_other['finalgrade']);
        $min = floatval($this->gradeitem->grademin);
        $max = floatval($this->gradeitem->grademax);

        // Define the result.
        $scaled = ($raw - $min) / ($max - $min);
        return [
            'raw' => round($raw, 2),
            'min' => round($min, 2),
            'max' => round($max, 2),
            'scaled' => round($scaled, 2)
        ];
    }
    
    /**
     * @return bool|null
     */
    protected function success() {
        $this->load_gradeitem();

        if (isset($this->gradeitem->gradepass) && $this->gradeitem->gradepass > 0) {
            $raw = floatval($this->event_other['finalgrade']);
            return $raw >= floatval($this->gradeitem->gradepass);
        }

        return null;
    }

    /**
     * @return string
     */
    protected function verb() {
        $success = $this->success();

        if (is_null($success)) {
            return 'https://w3id.org/xapi/tla/verbs/scored';
        }
        if ($success) {
            return 'https://adlnet.gov/expapi/verbs/passed';
        }
        return 'https://adlnet.gov/expapi/verbs/failed';
    }
    
    /**
     * @return array|null
     */
    protected function instructor() {
        if ($this->event->userid != $this->event->relateduserid) {
            return $this->user();
        }
        return null;
    }
}
