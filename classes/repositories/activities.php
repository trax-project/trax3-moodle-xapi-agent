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
use block_trax_xapi\utils;
use moodle_url;

class activities {

    // TODO Cache the courses, course modules, modules, module instances.

    /**
     * Get course props.
     *
     * @param int $mid
     * @return object
     */
    public function get_course_props(int $mid) {
        global $DB;
        $course = $DB->get_record('course', ['id' => $mid], '*', MUST_EXIST);

        return (object)[
            'iri' => $this->iri($mid, 'course'),
            'name' => utils::lang_string($course->fullname, $course),
            'component' => 'course',
            'url' => (new moodle_url('/course/view.php', ['id' => $mid]))->__toString(),
            'idnumber' => empty($course->idnumber) ? null : $course->idnumber,
        ];
    }

    /**
     * Get module props.
     *
     * @param int $mid
     * @return object
     */
    public function get_course_module_props(int $mid) {
        global $DB;
        $course_module = $DB->get_record('course_modules', ['id' => $mid], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $course_module->course], '*', MUST_EXIST);
        $module = $DB->get_record('modules', ['id' => $course_module->module], '*', MUST_EXIST);
        $instance = $DB->get_record($module->name, ['id' => $course_module->instance], '*', MUST_EXIST);
        $component = 'mod_' . $module->name;

        return (object)[
            'iri' => $this->iri($mid, $component),
            'name' => utils::lang_string($instance->name, $course),
            'component' => $component,
            'url' => (new moodle_url("/mod/$module->name/view.php", ['id' => $course_module->id]))->__toString(),
            'idnumber' => empty($course_module->idnumber) ? null : $course_module->idnumber,
        ];
    }

    /**
     * Get system props.
     *
     * @return object
     */
    public function get_system_props() {
        return (object)[
            'iri' => config::activities_id_base(),
        ];
    }

    /**
     * Get context props.
     *
     * @param int $mid
     * @return object
     * @throws \moodle_exception
     */
    public function get_context_props(int $mid) {
        global $DB;
        $context = $DB->get_record('context', ['id' => $mid], '*', MUST_EXIST);
        if ($context->contextlevel == 80) {
            // Block.
        }
        if ($context->contextlevel == 70) {
            return $this->get_course_module_props($context->instanceid);
        }
        if ($context->contextlevel == 50) {
            return $this->get_course_props($context->instanceid);
        }
        if ($context->contextlevel == 40) {
            // Course category.
        }
        if ($context->contextlevel == 30) {
            // User.
        }
        if ($context->contextlevel == 10) {
            return $this->get_system_props();
        }
        throw new \moodle_exception('exception_template_context', 'block_trax_xapi');
    }

    /**
     * Get an activity IRI.
     *
     * @param int $mid
     * @param string $type
     * @return string
     */
    public function iri(int $mid, string $type) {
        $base = config::activities_id_base();
        if (substr($base, -1) !== '/') {
            $base .= '/';
        }
        return $base . 'xapi/activities/' . $type . '/' . $mid;
    }
}
