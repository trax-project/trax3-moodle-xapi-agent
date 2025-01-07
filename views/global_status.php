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
use block_trax_xapi\client;
use block_trax_xapi\errors;
use block_trax_xapi\converter;

require('../../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_login();

// URL params.

$lrs = required_param('lrs', PARAM_INT);

// Page setup.

$PAGE->set_context(null); // hack - set context to something, by default to system context
config::require_admin();

$urlparams = [
    'lrs' => $lrs,
];

$baseurl = new moodle_url('/blocks/trax_xapi/views/global_status.php', $urlparams);
$PAGE->set_url($baseurl);

$title = get_string('global_status_'.$lrs, 'block_trax_xapi');
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$PAGE->navbar->add(get_string('pluginname', 'block_trax_xapi'));
$PAGE->navbar->add(get_string('xapi_status', 'block_trax_xapi'), $baseurl);
echo $OUTPUT->header();

// Content.

// ------------------------------ LINKS -----------------------------

if ($lrs == config::LRS_TEST) {
    $url = new moodle_url('/blocks/trax_xapi/views/global_status.php', [
        'lrs' => config::LRS_PRODUCTION,
    ]);

    echo("
        <div class='mb-5'>
            <a class='btn btn-primary' href='$url'>" . get_string('switch_production_lrs', 'block_trax_xapi') . "</a>
        </div>
    ");
} else {
    $url = new moodle_url('/blocks/trax_xapi/views/global_status.php', [
        'lrs' => config::LRS_TEST,
    ]);

    echo("
        <div class='mb-5'>
            <a class='btn btn-primary' href='$url'>" . get_string('switch_test_lrs', 'block_trax_xapi') . "</a>
        </div>
    ");
}

// ------------------------------------ MOODLE EVENTS -------------------------------------

echo('
    <h5 class="mb-3 mt-4">' . get_string('moodle_events', 'block_trax_xapi') . '</h5>
');
        
// Show errors.
if ($modelingErrors = errors::count_event_modeling_errors($lrs)) {
    echo('
        <p class="text-danger">
            <a href=" ' . new moodle_url("/blocks/trax_xapi/views/global_modeling_errors.php", [
                'source' => 'event',
                'lrs' => $lrs,
                'returnurl' => $baseurl,
            ]) . '
            " class="text-danger">
                '.get_string('event_modeling_errors_notice', 'block_trax_xapi', $modelingErrors).'
            </a>
        </p>
    ');
} else {
    echo('
        <p>'.get_string('no_error_notice', 'block_trax_xapi').'</p>
    ');
}

if (config::dev_tools_enabled()) {

    $url = (new moodle_url("/blocks/trax_xapi/actions/scan_logs.php", [
        'lrs' => $lrs,
        'returnurl' => $baseurl->__toString()
    ]))->__toString();

    echo("
        <a class='btn btn-secondary' href='$url'>" . get_string('test_scan_logs', 'block_trax_xapi') . "</a>
    ");
}

// -------------------------- SCORM DATA -----------------------------

echo('
    <h5 class="mb-3 mt-4">' . get_string('scorm_data', 'block_trax_xapi') . '</h5>
');

// Show errors.
if ($modelingErrors = errors::count_scorm_modeling_errors($lrs)) {
    echo('
        <p class="text-danger">
            <a href=" ' . new moodle_url("/blocks/trax_xapi/views/global_modeling_errors.php", [
                'source' => 'scorm',
                'lrs' => $lrs,
                'returnurl' => $baseurl->__toString()
            ]) . '
            " class="text-danger">
                '.get_string('scorm_modeling_errors_notice', 'block_trax_xapi', $modelingErrors).'
            </a>
        </p>
    ');
} else {
    echo('
        <p>'.get_string('no_error_notice', 'block_trax_xapi').'</p>
    ');
}

if (config::dev_tools_enabled()) {

    $url = (new moodle_url("/blocks/trax_xapi/actions/scan_scorm.php", [
        'lrs' => $lrs,
        'returnurl' => $baseurl->__toString()
    ]))->__toString();

    echo("
        <a class='btn btn-secondary' href='$url'>" . get_string('test_scan_scorm', 'block_trax_xapi') . "</a>
    ");
}

// ------------------------- LRS CLIENT --------------------------------

echo('
    <h5 class="mb-3 mt-4">' . get_string('lrs_client', 'block_trax_xapi') . '</h5>
');

if ($count = client::queue_size($lrs)) {
    echo('
        <p>'.get_string('client_status_n', 'block_trax_xapi', $count).'</p>
    ');
} else {
    echo('
        <p>'.get_string('client_status_0', 'block_trax_xapi').'</p>
    ');
}

// Get the client status.
$status = $DB->get_record('block_trax_xapi_client_status', [
    'lrs' => $lrs
]);

if ($status) {
    echo('
        <p class="text-success">'
        .get_string('client_task_last_run', 'block_trax_xapi', userdate($status->timestamp, "%d/%m/%Y at %H:%M"))
        .'</p>'
    );
} else if ($count) {
    echo('
        <p class="text-warning">'.get_string('client_task_never_run', 'block_trax_xapi').'</p>
    ');
}

if ($clientErrors = errors::count_client_errors($lrs)) {
    echo('
        <p class="text-danger">
            <a href=" ' . new moodle_url("/blocks/trax_xapi/views/client_errors.php", [
                'lrs' => $lrs,
                'returnurl' => $baseurl
            ]) . '
            " class="text-danger">
                '.get_string('client_errors_notice', 'block_trax_xapi', $clientErrors).'
            </a>
        </p>
    ');
}

if ($count && config::dev_tools_enabled()) {
    echo('<div class="mb-3">');

    $url = (new moodle_url("/blocks/trax_xapi/actions/flush_queue.php", [
        'lrs' => $lrs,
        'returnurl' => $baseurl->__toString()
    ]))->__toString();

    echo("
        <a class='btn btn-secondary' href='$url'>" . get_string('test_flush_statements', 'block_trax_xapi') . "</a>
    ");

    $url = (new moodle_url("/blocks/trax_xapi/actions/clear_queue.php", [
        'lrs' => $lrs,
        'returnurl' => $baseurl->__toString()
    ]))->__toString();

    echo("
        <a class='btn btn-secondary' href='$url'>" . get_string('test_clear_statements', 'block_trax_xapi') . "</a>
    ");
}


// -------------------------- COURSES -----------------------------

echo('
    <h5 class="mb-3 mt-4">' . get_string('courses', 'block_trax_xapi') . '</h5>
');

// Fetch data.

$configs = config::lrs_course_configs($lrs);

// Table setup.

$table = new flexible_table('course-configs');

$table->define_columns(['title', 'events', 'scorm']);
$table->define_headers([
    get_string('title', 'block_trax_xapi'),
    get_string('moodle_events', 'block_trax_xapi'),
    get_string('scorm_data', 'block_trax_xapi'),
]);
$table->define_baseurl($baseurl);

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'course-configs');
$table->set_attribute('class', 'generaltable generalbox');

$table->setup();

// Table content.

foreach($configs as $config) {
    $url = new moodle_url('/blocks/trax_xapi/views/course_status.php', [
        'courseid' => $config->courseid,
        'lrs' => $lrs,
    ]);
    $title = "<a href='$url'>" . $config->coursename . "</a>";

    switch ($config->events_mode) {
        case config::EVENTS_MODE_NO:
            $events = get_string('disabled', 'block_trax_xapi');
            break;
        case config::EVENTS_MODE_LIVE:
            $events = get_string('live', 'block_trax_xapi');
            break;
        case config::EVENTS_MODE_LOGS:
            $events = get_string('logs_since', 'block_trax_xapi', $config->logs_from);
            break;
    }

    switch ($config->scorm_enabled) {
        case config::SCORM_DISABLED:
            $scorm = get_string('disabled', 'block_trax_xapi');
            break;
        case config::SCORM_ENABLED:
            $scorm = get_string('attempts_since', 'block_trax_xapi', $config->scorm_from);
            break;
    }

    $table->add_data([$title, $events, $scorm]);
}
$table->print_html();


// ------------------------------ LINKS -----------------------------

if ($lrs == config::LRS_TEST) {
    $url = new moodle_url('/blocks/trax_xapi/views/global_status.php', [
        'lrs' => config::LRS_PRODUCTION,
    ]);

    echo("
        <hr class='mt-4'>
        <div class='mt-4'>
            <a class='btn btn-primary' href='$url'>" . get_string('switch_production_lrs', 'block_trax_xapi') . "</a>
        </div>
    ");
} else {
    $url = new moodle_url('/blocks/trax_xapi/views/global_status.php', [
        'lrs' => config::LRS_TEST,
    ]);

    echo("
        <hr class='mt-4'>
        <div class='mt-4'>
            <a class='btn btn-primary' href='$url'>" . get_string('switch_test_lrs', 'block_trax_xapi') . "</a>
        </div>
    ");
}


echo $OUTPUT->footer();
