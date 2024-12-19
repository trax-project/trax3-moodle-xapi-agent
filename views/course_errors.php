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

require('../../../config.php');
require_once($CFG->libdir . '/tablelib.php');

require_login();

// URL params.

$courseid = required_param('courseid', PARAM_INT);
$lrs = required_param('lrs', PARAM_INT);
$returnurl = required_param('returnurl', PARAM_URL);

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

$baseurl = new moodle_url('/blocks/trax_xapi/views/course_errors.php', $urlparams);
$PAGE->set_url($baseurl);

$title = get_string('course_errors', 'block_trax_xapi');
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_trax_xapi'));
$PAGE->navbar->add(get_string('course_errors', 'block_trax_xapi'), $baseurl);
echo $OUTPUT->header();

// Fetch data.

$errors = $DB->get_records('block_trax_xapi_errors', [
    'courseid' => $courseid,
    'lrs' => $lrs
]);
$errors = array_reverse($errors);

// Table setup.

$table = new flexible_table('course-errors');

$table->define_columns(['timestamp', 'code', 'eventname']);
$table->define_headers([
    get_string('timestamp', 'block_trax_xapi'),
    get_string('type', 'block_trax_xapi'),
    get_string('event', 'block_trax_xapi')
]);
$table->define_baseurl($baseurl);

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'course-errors');
$table->set_attribute('class', 'generaltable generalbox');

$table->column_class('timestamp', 'timestamp');
$table->column_class('code', 'code');
$table->column_class('event', 'event');

$table->setup();

// Table content.

foreach($errors as $error) {
    $timestamp = userdate($error->timestamp, "%d/%m/%Y at %H:%M");
    $code = get_string('error_code_' . $error->error, 'block_trax_xapi');
    $event = json_decode($error->data)->event->eventname;
    $table->add_data([$timestamp, $code, $event]);
}
$table->print_html();

// Delete errors.

$url = (new moodle_url("/blocks/trax_xapi/actions/delete_course_errors.php", $urlparams))->__toString();

echo '
    <div class="mb-3 mt-3">
        <a href="' . $url . '" class="btn btn-secondary">
        ' . get_string('course_errors_delete', 'block_trax_xapi') . '
        </a>
    </div>
';

// Return link.
echo '<div class="backlink mt-5">' . html_writer::link($returnurl, get_string('back')) . '</div>';

echo $OUTPUT->footer();
