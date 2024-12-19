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
//use moodle_url;

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
        global $COURSE, $DB, $OUTPUT;

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

        // Events mode.
        $this->content->text .= '<p>'.get_string('course_events_mode_'.$this->config->events_mode, 'block_trax_xapi', $this->config->logs_from).'</p>';

        // Log store mode.
        if ($this->config->events_mode == config::EVENTS_MODE_LOGS) {
            // Get the log status.
            if ($status = $DB->get_record('block_trax_xapi_logs_status', [
                'courseid' => $COURSE->id,
                'lrs' => $this->config->lrs
            ])) {
                $this->content->text .= '<p class="mb-2 text-success">'.get_string('logs_status_last_run', 'block_trax_xapi', userdate($status->timestamp, "%d/%m/%Y at %H:%M")).'</p>';

                $url = (new moodle_url("/blocks/trax_xapi/actions/replay_logs.php", [
                        'courseid' => $COURSE->id,
                        'lrs' => $this->config->lrs,
                        'returnurl' => $this->page->url->__toString()
                    ]))->__toString();

                $this->content->text .= '
                    <div class="mb-3">
                        <a href="' . $url . '" class="btn btn-secondary">
                        ' . get_string('logs_status_replay', 'block_trax_xapi') . '
                        </a>
                    </div>
                ';
            } else {
                $this->content->text .= '<p class="text-warning">'.get_string('logs_status_never_run', 'block_trax_xapi').'</p>';
            }
        }

        // SCORM mode.
        $this->content->text .= '<p>'.get_string('course_scorm_enabled_'.$this->config->scorm_enabled, 'block_trax_xapi', $this->config->scorm_from).'</p>';

        // SCORM data.
        if ($this->config->scorm_enabled == config::SCORM_ENABLED) {
            // Get the log status.
            if ($status = $DB->get_record('block_trax_xapi_scorm_status', [
                'courseid' => $COURSE->id,
                'lrs' => $this->config->lrs
            ])) {
                $this->content->text .= '<p class="mb-2 text-success">'.get_string('scorm_status_last_run', 'block_trax_xapi', userdate($status->timestamp, "%d/%m/%Y at %H:%M")).'</p>';

                $url = (new moodle_url("/blocks/trax_xapi/actions/replay_scorm.php", [
                        'courseid' => $COURSE->id,
                        'lrs' => $this->config->lrs,
                        'returnurl' => $this->page->url->__toString()
                    ]))->__toString();

                $this->content->text .= '
                    <div class="mb-3">
                        <a href="' . $url . '" class="btn btn-secondary">
                        ' . get_string('scorm_status_replay', 'block_trax_xapi') . '
                        </a>
                    </div>
                ';
            } else {
                $this->content->text .= '<p class="text-warning">'.get_string('scorm_status_never_run', 'block_trax_xapi').'</p>';
            }
        }

        // Show errors.
        $courseErrors = $DB->get_records('block_trax_xapi_errors', ['courseid' => $COURSE->id, 'lrs' => $this->config->lrs]);
        if (count($courseErrors)) {
            $this->content->text .= '<p class="text-danger">
                <a href=" ' . new moodle_url("/blocks/trax_xapi/views/course_errors.php", [
                    'courseid' => $COURSE->id,
                    'lrs' => $this->config->lrs,
                    'returnurl' => $this->page->url->__toString()
                ]) . '
                " class="text-danger">
                    '.get_string('course_errors_notice', 'block_trax_xapi', count($courseErrors)).'
                </a>
            </p>';
        }
        $otherErrors = $DB->get_records('block_trax_xapi_errors', ['courseid' => null, 'lrs' => $this->config->lrs]);
        if (count($otherErrors)) {
            $this->content->text .= '<p class="text-danger">
                <a href=" ' . new moodle_url("/blocks/trax_xapi/views/client_errors.php", [
                    'courseid' => $COURSE->id,
                    'lrs' => $this->config->lrs,
                    'returnurl' => $this->page->url->__toString()
                ]) . '
                " class="text-danger">
                    '.get_string('client_errors_notice', 'block_trax_xapi', count($otherErrors)).'
                </a>
            </p>';
        }

        // Test.
        if (is_siteadmin()) {
            $this->content->text .= '<p class="">
                <a href=" ' . new moodle_url("/blocks/trax_xapi/views/test.php", [
                    'courseid' => $COURSE->id,
                    'lrs' => $this->config->lrs,
                    'returnurl' => $this->page->url->__toString()
                ]) . '
                " class="">Test page for site admin (dev) ></a>
            </p>';
        }

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
