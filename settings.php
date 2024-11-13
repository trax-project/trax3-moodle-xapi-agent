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

defined('MOODLE_INTERNAL') || die;

use block_trax_xapi_agent\config;

if ($ADMIN->fulltree) {
    
    // -------------------- Production LRS -------------------- //

    $settings->add(new admin_setting_heading(
        'lrs',
        get_string('lrs_settings', 'block_trax_xapi_agent'),
        get_string('lrs_settings_help', 'block_trax_xapi_agent')
    ));

    // Endpoint.
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi_agent/lrs_endpoint',
        new lang_string('lrs_endpoint_prod', 'block_trax_xapi_agent'),
        new lang_string('lrs_endpoint_help', 'block_trax_xapi_agent'),
        'http://my.lrs/endpoint',
        PARAM_URL
    ));

    // Username.
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi_agent/lrs_username',
        new lang_string('lrs_username_prod', 'block_trax_xapi_agent'),
        new lang_string('lrs_username_help', 'block_trax_xapi_agent'),
        '',
        PARAM_TEXT
    ));

    // Password.
    $settings->add(new admin_setting_configpasswordunmask(
        'block_trax_xapi_agent/lrs_password',
        new lang_string('lrs_password_prod', 'block_trax_xapi_agent'),
        new lang_string('lrs_password_help', 'block_trax_xapi_agent'),
        '',
        PARAM_TEXT
    ));

    // -------------------- Test LRS -------------------- //

    $settings->add(new admin_setting_heading(
        'lrs2',
        get_string('lrs2_settings', 'block_trax_xapi_agent'),
        get_string('lrs2_settings_help', 'block_trax_xapi_agent')
    ));

    // Endpoint.
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi_agent/lrs2_endpoint',
        new lang_string('lrs_endpoint_test', 'block_trax_xapi_agent'),
        new lang_string('lrs_endpoint_help', 'block_trax_xapi_agent'),
        '',
        PARAM_URL
    ));

    // Username.
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi_agent/lrs2_username',
        new lang_string('lrs_username_test', 'block_trax_xapi_agent'),
        new lang_string('lrs_username_help', 'block_trax_xapi_agent'),
        '',
        PARAM_TEXT
    ));

    // Password.
    $settings->add(new admin_setting_configpasswordunmask(
        'block_trax_xapi_agent/lrs2_password',
        new lang_string('lrs_password_test', 'block_trax_xapi_agent'),
        new lang_string('lrs_password_help', 'block_trax_xapi_agent'),
        '',
        PARAM_TEXT
    ));

    // -------------------- Actors identification --------------------.

    $settings->add(new admin_setting_heading(
        'actors_id',
        get_string('actors_id', 'block_trax_xapi_agent'),
        get_string('actors_id_help', 'block_trax_xapi_agent')
    ));

    // Actors identification mode.
    $settings->add(new admin_setting_configselect(
        'block_trax_xapi_agent/actors_id_mode', 
        get_string('actors_id_mode', 'block_trax_xapi_agent'), 
        get_string('actors_id_mode_help', 'block_trax_xapi_agent'),
        config::ACTORS_ID_USERNAME,
        config::actors_identification_modes()
    ));

    // Actors identification custom field
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi_agent/actors_id_custom_field',
        new lang_string('actors_id_custom_field', 'block_trax_xapi_agent'),
        new lang_string('actors_id_custom_field_help', 'block_trax_xapi_agent'),
        '',
        PARAM_TEXT
    ));

    // Actors identification homepage.
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi_agent/actors_id_homepage',
        new lang_string('actors_id_homepage', 'block_trax_xapi_agent'),
        new lang_string('actors_id_homepage_help', 'block_trax_xapi_agent'),
        'http://my.moodle',
        PARAM_URL
    ));

    // Include actors name.
    $settings->add(new admin_setting_configcheckbox(
        'block_trax_xapi_agent/actors_id_include_name',
        new lang_string('actors_id_include_name', 'block_trax_xapi_agent'),
        new lang_string('actors_id_include_name_help', 'block_trax_xapi_agent'),
        1
    ));
  
    // -------------------- Activities identification --------------------.

    $settings->add(new admin_setting_heading(
        'activities_id',
        get_string('activities_id', 'block_trax_xapi_agent'),
        get_string('activities_id_help', 'block_trax_xapi_agent')
    ));

    // Activities IRI base.
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi_agent/activities_id_base',
        new lang_string('activities_id_base', 'block_trax_xapi_agent'),
        new lang_string('activities_id_base_help', 'block_trax_xapi_agent'),
        'http://my.moodle',
        PARAM_URL
    ));
    
    // -------------------- Moodle events --------------------.

    $settings->add(new admin_setting_heading(
        'moodle_events',
        get_string('moodle_events', 'block_trax_xapi_agent'),
        get_string('moodle_events_help', 'block_trax_xapi_agent')
    ));

    // Log modeling errors for events outside courses.
    $settings->add(new admin_setting_configcheckbox(
        'block_trax_xapi_agent/moodle_events_navigation',
        new lang_string('moodle_events_navigation', 'block_trax_xapi_agent'),
        new lang_string('moodle_events_navigation_help', 'block_trax_xapi_agent'),
        0
    ));

    // Log modeling errors for events outside courses.
    $settings->add(new admin_setting_configcheckbox(
        'block_trax_xapi_agent/moodle_events_completion',
        new lang_string('moodle_events_completion', 'block_trax_xapi_agent'),
        new lang_string('moodle_events_completion_help', 'block_trax_xapi_agent'),
        0
    ));

    // Log modeling errors for events outside courses.
    $settings->add(new admin_setting_configcheckbox(
        'block_trax_xapi_agent/moodle_events_grading',
        new lang_string('moodle_events_grading', 'block_trax_xapi_agent'),
        new lang_string('moodle_events_grading_help', 'block_trax_xapi_agent'),
        0
    ));
    
    // -------------------- xAPI modeling --------------------.

    $settings->add(new admin_setting_heading(
        'xapi_modeling',
        get_string('xapi_modeling', 'block_trax_xapi_agent'),
        get_string('xapi_modeling_help', 'block_trax_xapi_agent')
    ));

    // Activities IRI base.
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi_agent/custom_templates_folder',
        new lang_string('custom_templates_folder', 'block_trax_xapi_agent'),
        new lang_string('custom_templates_folder_help', 'block_trax_xapi_agent'),
        'local/trax_xapi_custom/templates',
        PARAM_TEXT
    ));

    // Activities IRI base.
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi_agent/custom_modelers_namespace',
        new lang_string('custom_modelers_namespace', 'block_trax_xapi_agent'),
        new lang_string('custom_modelers_namespace_help', 'block_trax_xapi_agent'),
        'local_trax_xapi_custom\modelers',
        PARAM_TEXT
    ));

    // -------------------- Errors management --------------------.

    /*
    $settings->add(new admin_setting_heading(
        'errors_management',
        get_string('errors_management', 'block_trax_xapi_agent'),
        get_string('errors_management_help', 'block_trax_xapi_agent')
    ));
    */
}
