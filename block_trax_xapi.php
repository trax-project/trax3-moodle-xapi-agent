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

use block_trax_xapi\config;

require_once($CFG->dirroot . '/lib/weblib.php');

class block_trax_xapi extends block_base {

    /**
     * Core function used to initialize the block.
     */
    public function init() {
        $this->title = get_string('block_title', 'block_trax_xapi');
    }

    /**
     * Allow the block to have a configuration page.
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Core function, specifies where the block can be used.
     *
     * @return array
     */
    public function applicable_formats() {
        return array('course-view' => true);
    }

    /**
     * Is each block of this type going to have instance-specific configuration?
     *
     * @return boolean
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Used to generate the content for the block.
     *
     * @return string
     */
    public function get_content() {
        global $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        // Check to see user can view/use the accessmap.
        $context = context_course::instance($COURSE->id);
        if (!isloggedin() || isguestuser() || !has_capability('block/trax_xapi:view', $context)) {
            return $this->content;
        }

        // No LRS.
        // No events mode.
        if (empty($this->config->lrs)) {
            $this->content->text = '<p>'.get_string('course_lrs_0', 'block_trax_xapi').'</p>';
            return $this->content;
        }

        // Selected LRS.
        $this->content->text = '<p>'.get_string('course_lrs_'.$this->config->lrs, 'block_trax_xapi').'</p>';
      
        $this->content->text .= '<p class="">';
        $this->content->text .= '
            <a class="btn btn-secondary" href=" ' . new moodle_url("/blocks/trax_xapi/views/course_status.php", [
                'courseid' => $COURSE->id,
                'lrs' => $this->config->lrs,
            ]) . '
            ">'.get_string('more_details', 'block_trax_xapi').'</a>';
        $this->content->text .= '</p>';

        return $this->content;
    }

    /**
     * Used to save the form config data.
     *
     * @param stdclass $data
     * @param bool $nolongerused
     */
    public function instance_config_save($data, $nolongerused = false) {
        parent::instance_config_save($data);
    }

    /**
     * Return the plugin config settings for external functions.
     *
     * @return stdClass the configs for both the block instance and plugin
     * @since Moodle 3.8
     */
    public function get_config_for_external() {
        // Return all settings for all users since it is safe (no private keys, etc..).
        $instanceconfigs = !empty($this->config) ? $this->config : new stdClass();
        $pluginconfigs = get_config('block_trax_xapi');

        return (object) [
            'instance' => $instanceconfigs,
            'plugin' => $pluginconfigs,
        ];
    }
}
