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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/grade/constants.php');

use block_trax_xapi\config;

class block_trax_xapi_edit_form extends block_edit_form {

    /**
     * The definition of the fields to use.
     *
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
        // Course settings.
        $mform->addElement('header', 'configheader', get_string('block_settings', 'block_trax_xapi'));

        // Course LRS.
        $mform->addElement('select', 'config_lrs',
            get_string('lrs', 'block_trax_xapi'),
            config::lrs_options()
        );
        $mform->setDefault('config_lrs', config::LRS_NO);

        // Events mode.
        $mform->addElement('select', 'config_events_mode',
            get_string('events_mode', 'block_trax_xapi'),
            config::events_mode_options()
        );
        $mform->setDefault('config_events_mode', config::EVENTS_MODE_NO);

        // From date (logs)
        $mform->addElement('text', 'config_logs_from',
            get_string('logs_from', 'block_trax_xapi')
        );
        $mform->setDefault('config_logs_from', date('d/m/Y', time()));
        $mform->setType('config_logs_from', PARAM_TEXT);
        $mform->hideIf('config_logs_from', 'config_events_mode', 'neq', config::EVENTS_MODE_LOGS);

        // SCORM data.
        $mform->addElement('select', 'config_scorm_enabled',
            get_string('collect_scorm_data', 'block_trax_xapi'),
            config::scorm_enabled_options()
        );
        $mform->setDefault('config_scorm_enabled', config::SCORM_DISABLED);

        // From date (logs)
        $mform->addElement('text', 'config_scorm_from',
            get_string('scorm_from', 'block_trax_xapi')
        );
        $mform->setDefault('config_scorm_from', date('d/m/Y', time()));
        $mform->setType('config_scorm_from', PARAM_TEXT);
        $mform->hideIf('config_scorm_from', 'config_scorm_enabled', 'eq', config::SCORM_DISABLED);
    }

    /**
     * Display the configuration form when block is being added to the page
     *
     * @return bool
     */
    public static function display_form_when_adding(): bool {
        return true;
    }
}