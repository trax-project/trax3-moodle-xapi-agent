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
use block_trax_xapi\sources\logs\scanner as LogsScanner;
use block_trax_xapi\sources\scorm\scanner as ScormScanner;

require('../../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_login();

if (!is_siteadmin()) {
    throw new \Exception('This page is accessible only to the site administrator.');
}
if (!config::dev_tools_enabled()) {
    throw new \Exception('You must enable dev tools in the TRAX xAPI plugin in order to access this page.');
}

// URL params.

$courseid = required_param('courseid', PARAM_INT);
$lrs = required_param('lrs', PARAM_INT);
$returnurl = required_param('returnurl', PARAM_URL);
$test = optional_param('test', '', PARAM_TEXT);

// Page setup.

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$PAGE->set_course($course);
$context = $PAGE->context;

require_capability('block/trax_xapi:view', $context);

$urlparams = [
    'courseid' => $courseid,
    'lrs' => $lrs,
    'returnurl' => $returnurl,
];

$baseurl = new moodle_url('/blocks/trax_xapi/views/course_test.php', $urlparams);
$PAGE->set_url($baseurl);

$title = 'Test page';
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_trax_xapi'));
$PAGE->navbar->add('Test page', $baseurl);
echo $OUTPUT->header();

// Content.

if (empty($test)) {
    echo '<p>No test running.</p>';
}

if ($test == 'logs') {
    echo '<p>Running logs scanner...</p>';
    LogsScanner::run($courseid);
    echo '<p>Done!</p>';
}

if ($test == 'scorm') {
    echo '<p>Running SCORM scanner...</p>';
    ScormScanner::run($courseid);
    echo '<p>Done!</p>';
}

if ($test == 'flush') {
    echo '<p>Sending statements from queue...</p>';
    client::flush();
    echo '<p>Done!</p>';
}

// Links.

$queueSize = client::queue_size($lrs);

echo "<div class='mt-5'>
    <a class='btn btn-secondary' href='$baseurl&test=logs'>Run logs scanner</a>
    <a class='btn btn-secondary ml-1' href='$baseurl&test=scorm'>Run SCORM scanner</a>
    <a class='btn btn-secondary ml-1' href='$baseurl&test=flush'>Flush statements queue ($queueSize)</a>
    <a class='btn btn-primary ml-1' href='$returnurl'>Go back to course</a>
</div>";

echo $OUTPUT->footer();
