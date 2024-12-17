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

defined('MOODLE_INTERNAL') || die;

use block_trax_xapi\config;

if ($ADMIN->fulltree) {
    
    // -------------------- Production LRS -------------------- //

    $settings->add(new admin_setting_heading(
        'lrs',
        get_string('lrs_settings', 'block_trax_xapi'),
        get_string('lrs_settings_help', 'block_trax_xapi')
    ));

    // Endpoint.
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi/lrs_endpoint',
        get_string('lrs_endpoint_prod', 'block_trax_xapi'),
        get_string('lrs_endpoint_help', 'block_trax_xapi'),
        'http://my.lrs/endpoint',
        PARAM_URL
    ));

    // Username.
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi/lrs_username',
        get_string('lrs_username_prod', 'block_trax_xapi'),
        get_string('lrs_username_help', 'block_trax_xapi'),
        '',
        PARAM_TEXT
    ));

    // Password.
    $settings->add(new admin_setting_configpasswordunmask(
        'block_trax_xapi/lrs_password',
        get_string('lrs_password_prod', 'block_trax_xapi'),
        get_string('lrs_password_help', 'block_trax_xapi'),
        '',
        PARAM_TEXT
    ));

    // -------------------- Test LRS -------------------- //

    $settings->add(new admin_setting_heading(
        'lrs2',
        get_string('lrs2_settings', 'block_trax_xapi'),
        get_string('lrs2_settings_help', 'block_trax_xapi')
    ));

    // Endpoint.
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi/lrs2_endpoint',
        get_string('lrs_endpoint_test', 'block_trax_xapi'),
        get_string('lrs_endpoint_help', 'block_trax_xapi'),
        '',
        PARAM_URL
    ));

    // Username.
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi/lrs2_username',
        get_string('lrs_username_test', 'block_trax_xapi'),
        get_string('lrs_username_help', 'block_trax_xapi'),
        '',
        PARAM_TEXT
    ));

    // Password.
    $settings->add(new admin_setting_configpasswordunmask(
        'block_trax_xapi/lrs2_password',
        get_string('lrs_password_test', 'block_trax_xapi'),
        get_string('lrs_password_help', 'block_trax_xapi'),
        '',
        PARAM_TEXT
    ));

    // -------------------- Actors identification --------------------.

    $settings->add(new admin_setting_heading(
        'actors_id',
        get_string('actors_id', 'block_trax_xapi'),
        get_string('actors_id_help', 'block_trax_xapi')
    ));

    // Actors identification mode.
    $settings->add(new admin_setting_configselect(
        'block_trax_xapi/actors_id_mode', 
        get_string('actors_id_mode', 'block_trax_xapi'), 
        get_string('actors_id_mode_help', 'block_trax_xapi'),
        config::ACTORS_ID_USERNAME,
        config::actors_identification_modes()
    ));

    // Actors identification custom field
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi/actors_id_custom_field',
        get_string('actors_id_custom_field', 'block_trax_xapi'),
        get_string('actors_id_custom_field_help', 'block_trax_xapi'),
        '',
        PARAM_TEXT
    ));

    // Actors identification homepage.
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi/actors_id_homepage',
        get_string('actors_id_homepage', 'block_trax_xapi'),
        get_string('actors_id_homepage_help', 'block_trax_xapi'),
        'http://my.moodle',
        PARAM_URL
    ));

    // Include actors name.
    $settings->add(new admin_setting_configcheckbox(
        'block_trax_xapi/actors_id_include_name',
        get_string('actors_id_include_name', 'block_trax_xapi'),
        get_string('actors_id_include_name_help', 'block_trax_xapi'),
        1
    ));
  
    // -------------------- Activities identification --------------------.

    $settings->add(new admin_setting_heading(
        'activities_id',
        get_string('activities_id', 'block_trax_xapi'),
        get_string('activities_id_help', 'block_trax_xapi')
    ));

    // Activities IRI base.
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi/activities_id_base',
        get_string('activities_id_base', 'block_trax_xapi'),
        get_string('activities_id_base_help', 'block_trax_xapi'),
        'http://my.moodle',
        PARAM_URL
    ));
    
    // -------------------- Moodle events --------------------.

    $settings->add(new admin_setting_heading(
        'moodle_events',
        get_string('moodle_events', 'block_trax_xapi'),
        get_string('moodle_events_help', 'block_trax_xapi')
    ));

    foreach (config::supported_domains() as $plugin => $domains) {
        foreach ($domains as $domain) {
            $settings->add(new admin_setting_configcheckbox(
                "block_trax_xapi/moodle_events_$domain",
                get_string("moodle_events_$domain", $plugin),
                get_string("moodle_events_$domain" . '_help', $plugin),
                0
            ));
        }
    }
    
    // -------------------- xAPI modeling --------------------.

    $settings->add(new admin_setting_heading(
        'xapi_modeling',
        get_string('xapi_modeling', 'block_trax_xapi'),
        get_string('xapi_modeling_help', 'block_trax_xapi')
    ));

    // Customization plugin.
    $settings->add(new admin_setting_configtext(
        'block_trax_xapi/custom_plugin',
        get_string('custom_plugin', 'block_trax_xapi'),
        get_string('custom_plugin_help', 'block_trax_xapi'),
        'trax_xapi_custom',
        PARAM_TEXT
    ));
}
