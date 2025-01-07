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
use block_trax_xapi\errors;

require('../../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_login();

// URL params.

$source = required_param('source', PARAM_TEXT);
$courseid = required_param('courseid', PARAM_INT);
$lrs = required_param('lrs', PARAM_INT);
$returnurl = required_param('returnurl', PARAM_URL);

// Page setup.

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$PAGE->set_course($course);
$context = $PAGE->context;

require_capability('block/trax_xapi:view', $context);

$urlparams = [
    'source' => $source,
    'courseid' => $courseid,
    'lrs' => $lrs,
    'returnurl' => $returnurl,
];

$baseurl = new moodle_url('/blocks/trax_xapi/views/course_modeling_errors.php', $urlparams);
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

// Title.

echo('<h2 class="mb-4">' . get_string($source.'_modeling_errors', 'block_trax_xapi') . '</h2>');

// Fetch data.

$method = 'get_' . $source . '_logs';
$errors = errors::$method($lrs, $courseid);

// Links.

$retryurl = (new moodle_url("/blocks/trax_xapi/actions/retry_course_modeling_errors.php", $urlparams))->__toString();
$deleteurl = (new moodle_url("/blocks/trax_xapi/actions/delete_course_modeling_errors.php", $urlparams))->__toString();

echo '
    <div class="mb-3 mt-3">
';
if (config::is_admin()) {
    echo '
            <a href="' . $retryurl . '" class="btn btn-secondary">
            ' . get_string('retry', 'block_trax_xapi') . '
            </a>
    ';
    echo '
            <a href="' . $deleteurl . '" class="btn btn-secondary">
            ' . get_string('forget', 'block_trax_xapi') . '
            </a>
    ';
}
echo '
        <a class="btn btn-primary" href="' . $returnurl . '">
        ' . get_string('back', 'block_trax_xapi') . '
        </a>
';
echo '
    </div>
';

// Table setup.

$table = new flexible_table('course-errors');

$table->define_columns(['timestamp', 'code', 'template']);
$table->define_headers([
    get_string('timestamp', 'block_trax_xapi'),
    get_string('type', 'block_trax_xapi'),
    get_string('template', 'block_trax_xapi')
]);
$table->define_baseurl($baseurl);

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'course-errors');
$table->set_attribute('class', 'generaltable generalbox');

$table->column_class('timestamp', 'timestamp');
$table->column_class('code', 'code');
$table->column_class('template', 'template');

$table->setup();

// Table content.

foreach($errors as $error) {
    $timestamp = userdate($error->timestamp, "%d/%m/%Y at %H:%M");
    $code = get_string('modeling_error_code_' . $error->error, 'block_trax_xapi');
    $template = json_decode($error->data)->template;
    $table->add_data([$timestamp, $code, $template]);
}
$table->print_html();

// Delete errors.

echo '
    <div class="mb-3 mt-3">
';
if (config::is_admin()) {
    echo '
            <a href="' . $retryurl . '" class="btn btn-secondary">
            ' . get_string('retry', 'block_trax_xapi') . '
            </a>
    ';
    echo '
            <a href="' . $deleteurl . '" class="btn btn-secondary">
            ' . get_string('forget', 'block_trax_xapi') . '
            </a>
    ';
}
echo '
        <a class="btn btn-primary" href="' . $returnurl . '">
        ' . get_string('back', 'block_trax_xapi') . '
        </a>
';
echo '
    </div>
';

echo $OUTPUT->footer();
