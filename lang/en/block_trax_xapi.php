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

// Plugin.
$string['pluginname'] = 'TRAX xAPI Agent';
$string['trax_xapi:addinstance'] = 'Add the block';
$string['trax_xapi:view'] = 'View block';
$string['block_title'] = 'xAPI';
$string['block_settings'] = 'xAPI settings for this course';

// Privacy.
$string['privacy:metadata'] = 'The TRAX xAPI Agent plugin does not store any personal data about any user.';

// Common.
$string['yes'] = 'Yes';
$string['no'] = 'No';

// LRS configuration.
$string['lrs_settings'] = 'Production LRS';
$string['lrs_settings_help'] = "The following settings should be found in your LRS.
    If you did'n choose an LRS yet, you should take a look at 
    <a href='http://traxlrs.com' target='_blank'>TRAX LRS</a>.
    However, this plugin should work with any 
    <a href='https://adopters.adlnet.gov/products/all/0' target='_blank'>xAPI compliant LRS</a>.";

$string['lrs2_settings'] = 'Test LRS';
$string['lrs2_settings_help'] = "Optionaly, you may configure a test LRS.
    This may be helpful to make some tests in specific courses, without affecting your production LRS.";

$string['lrs_endpoint_prod'] = 'Production LRS endpoint';
$string['lrs_endpoint_test'] = 'Test LRS endpoint';
$string['lrs_endpoint_help'] = "This is the URL used to call the xAPI services of your LRS.";

$string['lrs_username_prod'] = 'Production LRS username (Basic HTTP)';
$string['lrs_username_test'] = 'Test LRS username (Basic HTTP)';
$string['lrs_username_help'] = "This is the username of the Basic HTTP account created on your LRS.";

$string['lrs_password_prod'] = 'Production LRS password (Basic HTTP)';
$string['lrs_password_test'] = 'Test LRS password (Basic HTTP)';
$string['lrs_password_help'] = "This is the password of the Basic HTTP account created on your LRS.";

// LRS options.
$string['lrs'] = 'LRS';
$string['lrs_no'] = 'No LRS';
$string['lrs_production'] = 'Production LRS';
$string['lrs_test'] = 'Test LRS';

// Show LRS option in course.
$string['course_lrs_0'] = 'xAPI is disabled for this course.';
$string['course_lrs_1'] = 'This course sends data to the <b>production LRS</b>.';
$string['course_lrs_2'] = 'This course sends data to the <b>test LRS</b>.';

// Events mode options.
$string['events_mode'] = 'Events mode';
$string['events_mode_no'] = 'Events not catched';
$string['events_mode_live'] = 'Events catched in real time';
$string['events_mode_logs'] = 'Events collected from the log store';
$string['logs_from'] = 'Logs recorded since';

// SCORM options.
$string['collect_scorm_data'] = 'Collect SCORM data';
$string['scorm_from'] = 'SCORM attempts since';

// Show events mode in course.
$string['course_events_mode_0'] = 'Events are <b>not catched</b> in this course.';
$string['course_events_mode_1'] = 'Events are catched in <b>real time</b>.';
$string['course_events_mode_2'] = 'Events are collected from the <b>log store</b> since the {$a}.';

// Show scorm data collection in course.
$string['course_scorm_enabled_0'] = 'SCORM data is <b>not collected</b> from this course.';
$string['course_scorm_enabled_1'] = 'SCORM data is collected from the <b>SCORM activities</b> since the {$a}.';

// Actors identification.
$string['actors_id'] = 'Actors identification';
$string['actors_id_help'] = 'In this section, you can define how actors should be identified in the xAPI statements.';

$string['actors_id_mode'] = 'Actors identification mode';
$string['actors_id_mode_help'] = 'By default, actors are identified by this method.';

$string['actors_id_username'] = 'Username (account format)';
$string['actors_id_dbid'] = 'Database ID (account format)';
$string['actors_id_uuid'] = 'UUID (account format)';
$string['actors_id_email'] = 'Email (mbox format)';

$string['actors_id_custom_field'] = 'Custom field';
$string['actors_id_custom_field_help'] = 'Users who have this custom field defined are identified with the custom field value in the `account->name` property.';

$string['actors_id_homepage'] = 'Homepage';
$string['actors_id_homepage_help'] = 'Value of the `homePage` property for the `account` format.';

$string['actors_id_include_name'] = 'Include firstname and lastname';
$string['actors_id_include_name_help'] = 'Include the firstname and lastname in the `name` property of the actors.';

// Activities identification.
$string['activities_id'] = 'Platform and activities identification';
$string['activities_id_help'] = 'In this section, you can define how the system and activities should be identified in the xAPI statements.';

$string['activities_id_base'] = 'Platform IRI';
$string['activities_id_base_help'] = 'All the activity IRIs will start with this base.';

// Moodle events.
$string['moodle_events'] = 'Moodle events';
$string['moodle_events_help'] = 'In this section, you can select the Moodle events you want to track as xAPI data.';

$string['moodle_events_navigation'] = 'Navigation';
$string['moodle_events_navigation_help'] = 'Navigation in courses and course modules is tracked with the `viewed` verb.';

$string['moodle_events_completion'] = 'Completion';
$string['moodle_events_completion_help'] = 'Courses and course modules completion is tracked with the `completed` and `voided-completion` verbs.';

$string['moodle_events_grading'] = 'Grading';
$string['moodle_events_grading_help'] = 'Course modules grading is tracked with the `scored`, `passed`, `failed` and `voided-score` verbs.';

$string['moodle_events_authentication'] = 'Authentication';
$string['moodle_events_authentication_help'] = 'Users authentication is tracked with the `logged-in` and `logged-out` verbs.
    System level events must be enabled.
';

$string['moodle_events_h5p'] = 'H5P';
$string['moodle_events_h5p_help'] = 'H5P xAPI events are tracked. Refer to H5P docs for further details.';

// System events.
$string['system_events'] = 'System level events';
$string['system_events_help'] = 'In this section, you can configure the way this plugin catches the system level events.
    System level events are events generated outside courses, such as authentication events.
';

$string['system_events_lrs'] = 'LRS';
$string['system_events_lrs_help'] = '';

$string['system_events_mode'] = 'Events mode';
$string['system_events_mode_help'] = '';

$string['system_events_from'] = 'Logs recorded since';
$string['system_events_from_help'] = 'Enter a date if you want to collect events from the log store.';

// xAPI modeling.
$string['xapi_modeling'] = 'xAPI modeling';
$string['xapi_modeling_help'] = 'In this section, you can define your own xAPI modeling customization.';

$string['custom_plugin'] = 'Customization plugin';
$string['custom_plugin_help'] = "The customization plugin must be a local plugin, located in the './local' folder, following the
<a href='https://github.com/trax-project/trax3-moodle-xapi-agent/blob/master/docs/customization.md' target='_blank'>customization guidelines</a>.
Please, enter its name without the 'local_' prefix.";

$string['custom_templates_folder'] = 'Custom templates folder';
$string['custom_templates_folder_help'] = 'You can use a custom folder (typically `local/trax_xapi/templates`) to provide your own xAPI templates.';

$string['custom_modelers_namespace'] = 'Custom modelers namespace';
$string['custom_modelers_namespace_help'] = 'You can use a custom namespace (typically `local_trax_xapi\modelers`) to provide your own xAPI modelers.';

// Log store.
$string['logs_status_never_run'] = 'Logs from this course will be scanned for the first time during the next CRON job.';
$string['logs_status_last_run'] = 'Logs from this course has been scanned on the {$a} for the last time.';
$string['scorm_status_replay'] = 'Rescan from the begining';

// SCORM data.
$string['scorm_status_never_run'] = 'SCORM data from this course will be scanned for the first time during the next CRON job.';
$string['scorm_status_last_run'] = 'SCORM data from this course has been scanned on the {$a} for the last time.';
$string['scorm_status_replay'] = 'Rescan from the begining';

// Exceptions.
$string['exception_template_context'] = 'TRAX xAPI Agent: we were not able to retrieve the context refered in a template.';
$string['exception_entry_not_found'] = 'TRAX xAPI Agent: entry not found.';

// Errors management.
$string['errors_management'] = 'Errors management';
$string['errors_management_help'] = '';

$string['course_errors'] = 'xAPI modeling errors';
$string['course_errors_notice'] = '{$a} error(s) occurred during xAPI data modeling for this course.';
$string['course_errors_delete'] = 'Delete xAPI modeling errors';
$string['client_errors'] = 'LRS communication errors';
$string['client_errors_notice'] = '{$a} error(s) occurred during communication with the LRS.';
$string['client_errors_delete'] = 'Delete LRS communication errors';
$string['show_details'] = 'Show details';

$string['error_code_2'] = 'Template file opening';
$string['error_code_3'] = 'Template JSON parsing';
$string['error_code_4'] = 'Placeholder generation';
$string['error_code_http'] = 'HTTP client';

$string['timestamp'] = 'Date/Time';
$string['type'] = 'Type';
$string['event'] = 'Event';

// Tasks.
$string['task_scan_log_store'] = 'Scan the log store to create xAPI statements';
$string['task_scan_scorm_data'] = 'Scan SCORM data to create xAPI statements';
