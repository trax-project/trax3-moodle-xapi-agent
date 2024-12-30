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

class sco_assessed extends base {

    /**
     * Get the JSON template.
     *
     * @return string|false
     */
    protected function template() {
        return 'scorm/sco_assessed';
    }
    
    /**
     * @return string|null
     */
    protected function verb() {
        if (isset($this->attempt->values['cmi.success_status']) && $this->attempt->values['cmi.success_status'] == 'passed') {    // SCORM 2004
            return 'http://adlnet.gov/expapi/verbs/passed';
        }
        if (isset($this->attempt->values['cmi.core.lesson_status']) && $this->attempt->values['cmi.core.lesson_status'] == 'passed') {      // SCORM 1.2
            return 'http://adlnet.gov/expapi/verbs/passed';
        }
        return 'http://adlnet.gov/expapi/verbs/failed';
    }
}
