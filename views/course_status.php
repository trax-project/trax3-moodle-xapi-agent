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

$courseid = required_param('courseid', PARAM_INT);
$lrs = required_param('lrs', PARAM_INT);

// Page setup.

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$PAGE->set_course($course);
$context = $PAGE->context;

require_capability('block/trax_xapi:view', $context);

$urlparams = [
    'courseid' => $courseid,
    'lrs' => $lrs,
];

$baseurl = new moodle_url('/blocks/trax_xapi/views/course_status.php', $urlparams);
$PAGE->set_url($baseurl);

$title = $course->fullname;
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_trax_xapi'));
$PAGE->navbar->add($course->shortname);
$PAGE->navbar->add(get_string('xapi_status', 'block_trax_xapi'), $baseurl);
echo $OUTPUT->header();

// Content.

$config = config::course_config($courseid);

echo('<h2>' . get_string('xapi_status', 'block_trax_xapi') . '</h2>');

// ------------------------------------ MOODLE EVENTS -------------------------------------

echo('
    <h5 class="mb-3 mt-4">' . get_string('moodle_events', 'block_trax_xapi') . '</h5>
');
echo('
    <p>'.get_string('course_events_mode_'.$config->events_mode, 'block_trax_xapi', $config->logs_from).'</p>
');

// Log store mode.
if ($config->events_mode == config::EVENTS_MODE_LOGS) {

    $status = $DB->get_record('block_trax_xapi_logs_status', [
        'courseid' => $courseid,
        'lrs' => $config->lrs
    ]);

    // Get the log status.
    if ($status) {
        echo('
            <p class="text-success">'.get_string('logs_status_last_run', 'block_trax_xapi', userdate($status->timestamp, "%d/%m/%Y at %H:%M")).'</p>
        ');
    } else {
        echo('
            <p class="text-warning">'.get_string('logs_status_never_run', 'block_trax_xapi').'</p>
        ');
    }
}
        
// Show errors.
if ($modelingErrors = errors::count_event_modeling_errors($config->lrs, $courseid)) {
    echo('
        <p class="text-danger">
            <a href=" ' . new moodle_url("/blocks/trax_xapi/views/course_modeling_errors.php", [
                'source' => 'event',
                'courseid' => $courseid,
                'lrs' => $config->lrs,
                'returnurl' => $baseurl
            ]) . '
            " class="text-danger">
                '.get_string('event_modeling_errors_notice', 'block_trax_xapi', $modelingErrors).'
            </a>
        </p>
    ');
}

if ($config->events_mode == config::EVENTS_MODE_LOGS) {
    echo('<div>');
    if ($status && config::is_authorized_teacher($context)) {
        $url = (new moodle_url("/blocks/trax_xapi/actions/replay_logs.php", [
            'courseid' => $courseid,
            'lrs' => $config->lrs,
            'returnurl' => $baseurl->__toString()
        ]))->__toString();

        echo('
            <a href="' . $url . '" class="btn btn-secondary">'
            . get_string('logs_status_replay', 'block_trax_xapi')
            . '</a>
        ');
    }
    if (config::is_admin() && config::dev_tools_enabled()) {
        $url = (new moodle_url("/blocks/trax_xapi/actions/scan_logs.php", [
            'courseid' => $courseid,
            'returnurl' => $baseurl->__toString()
        ]))->__toString();

        echo("
            <a class='btn btn-secondary' href='$url'>" . get_string('test_scan_logs', 'block_trax_xapi') . "</a>
        ");
    }
    echo('</div>');
}

// -------------------------- SCORM DATA -----------------------------

echo('<h5 class="mb-3 mt-4">' . get_string('scorm_data', 'block_trax_xapi') . '</h5>');
echo('
    <p>'
    .get_string('course_scorm_enabled_'.$config->scorm_enabled, 'block_trax_xapi', $config->scorm_from)
    .'</p>
');

if ($config->scorm_enabled == config::SCORM_ENABLED) {

    $status = $DB->get_record('block_trax_xapi_scorm_status', [
        'courseid' => $courseid,
        'lrs' => $config->lrs
    ]);

    // Get the log status.
    if ($status) {
        echo('
            <p class="text-success">'
            .get_string('scorm_status_last_run', 'block_trax_xapi', userdate($status->timestamp, "%d/%m/%Y at %H:%M"))
            .'</p>
        ');

        $url = (new moodle_url("/blocks/trax_xapi/actions/replay_scorm.php", [
                'courseid' => $courseid,
                'lrs' => $config->lrs,
                'returnurl' => $baseurl->__toString()
            ]))->__toString();
    } else {
        echo('<p class="text-warning">'.get_string('scorm_status_never_run', 'block_trax_xapi').'</p>');
    }
}

// Show errors.
if ($modelingErrors = errors::count_scorm_modeling_errors($config->lrs, $courseid)) {
    echo('<p class="text-danger">
            <a href=" ' . new moodle_url("/blocks/trax_xapi/views/course_modeling_errors.php", [
                'source' => 'scorm',
                'courseid' => $courseid,
                'lrs' => $config->lrs,
                'returnurl' => $baseurl
            ]) . '
            " class="text-danger">
                '.get_string('scorm_modeling_errors_notice', 'block_trax_xapi', $modelingErrors).'
            </a>
        </p>
    ');
}

if ($config->scorm_enabled == config::SCORM_ENABLED) {
    echo('<div>');
    if ($status && config::is_authorized_teacher($context)) {
        echo('
            <a href="' . $url . '" class="btn btn-secondary">
            ' . get_string('scorm_status_replay', 'block_trax_xapi') . '
            </a>
        ');
    }
    if (config::is_admin() && config::dev_tools_enabled()) {

        $url = (new moodle_url("/blocks/trax_xapi/actions/scan_scorm.php", [
            'courseid' => $courseid,
            'returnurl' => $baseurl->__toString()
        ]))->__toString();

        echo("
            <a class='btn btn-secondary' href='$url'>" . get_string('test_scan_scorm', 'block_trax_xapi') . "</a>
        ");
    }
    echo('</div>');
}

// ------------------------- LRS CLIENT --------------------------------

if ($config->events_mode == config::EVENTS_MODE_LOGS || $config->scorm_enabled == config::SCORM_ENABLED) {
    echo('<h5 class="mb-3 mt-4">' . get_string('lrs_client', 'block_trax_xapi') . '</h5>');

    if ($count = client::queue_size($config->lrs, $courseid)) {
        echo('<p>'.get_string('client_status_n', 'block_trax_xapi', $count).'</p>');
    } else {
        echo('<p>'.get_string('client_status_0', 'block_trax_xapi').'</p>');
    }

    // Get the client status.
    $status = $DB->get_record('block_trax_xapi_client_status', [
        'lrs' => $config->lrs
    ]);

    if ($status) {
        echo('
            <p class="text-success">'
            .get_string('client_task_last_run', 'block_trax_xapi', userdate($status->timestamp, "%d/%m/%Y at %H:%M"))
            .'</p>'
        );
    } else if ($count) {
        echo('<p class="text-warning">'.get_string('client_task_never_run', 'block_trax_xapi').'</p>');
    }

    if ($clientErrors = errors::count_client_errors($config->lrs)) {
        if (config::is_admin()) {
            echo('<p class="text-danger">
                    <a href=" ' . new moodle_url("/blocks/trax_xapi/views/client_errors.php", [
                        'lrs' => $config->lrs,
                        'returnurl' => $baseurl
                    ]) . '
                    " class="text-danger">
                        '.get_string('client_errors_notice', 'block_trax_xapi', $clientErrors).'
                    </a>
                </p>
            ');
        } else {
            echo('<p class="text-danger">'
                . get_string('client_errors_notice', 'block_trax_xapi', $clientErrors)
                . '</p>');
        }
    }

    if ($count) {
        echo('<div>');
        if (config::is_admin() && config::dev_tools_enabled()) {

            $url = (new moodle_url("/blocks/trax_xapi/actions/flush_queue.php", [
                'courseid' => $courseid,
                'lrs' => $config->lrs,
                'returnurl' => $baseurl->__toString()
            ]))->__toString();

            echo("
                <a class='btn btn-secondary' href='$url'>" . get_string('test_flush_statements', 'block_trax_xapi') . "</a>
            ");

            $url = (new moodle_url("/blocks/trax_xapi/actions/clear_queue.php", [
                'courseid' => $courseid,
                'lrs' => $config->lrs,
                'returnurl' => $baseurl->__toString()
            ]))->__toString();

            echo("
                <a class='btn btn-secondary' href='$url'>" . get_string('test_clear_statements', 'block_trax_xapi') . "</a>
            ");
        }
        echo('</div>');
    }
}

// ------------------------------ LINKS ------------------------------

echo("
    <hr class='mt-4'>
    <div class='mt-4'>
");
if (config::is_admin()) {
    echo("
        <a class='btn btn-secondary' href='" . new moodle_url("/blocks/trax_xapi/views/global_status.php", ['lrs' => $config->lrs])
        . "'>" . get_string('global_status', 'block_trax_xapi') . "</a>
    ");
}
echo("
        <a class='btn btn-primary' href='" . new moodle_url("/course/view.php", ['id' => $courseid])
        . "'>
        " . get_string('back_to_course', 'block_trax_xapi') . "
        </a>
    </div>
");

echo $OUTPUT->footer();
