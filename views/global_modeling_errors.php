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
$lrs = required_param('lrs', PARAM_INT);
$returnurl = required_param('returnurl', PARAM_URL);

// Page setup.

$PAGE->set_context(null); // hack - set context to something, by default to system context
config::require_admin();

$urlparams = [
    'source' => $source,
    'lrs' => $lrs,
    'returnurl' => $returnurl,
];

$baseurl = new moodle_url('/blocks/trax_xapi/views/global_modeling_errors.php', $urlparams);
$PAGE->set_url($baseurl);

$title = get_string($source.'_modeling_errors', 'block_trax_xapi');
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$PAGE->navbar->add(get_string('pluginname', 'block_trax_xapi'));
$PAGE->navbar->add(get_string($source.'_modeling_errors', 'block_trax_xapi'), $baseurl);
echo $OUTPUT->header();


// Links.

$retryurl = (new moodle_url("/blocks/trax_xapi/actions/retry_global_modeling_errors.php", $urlparams))->__toString();
$deleteurl = (new moodle_url("/blocks/trax_xapi/actions/delete_global_modeling_errors.php", $urlparams))->__toString();

echo '
    <div class="mb-3 mt-3">
        <a href="' . $retryurl . '" class="btn btn-secondary">
        ' . get_string('retry', 'block_trax_xapi') . '
        </a>
        <a href="' . $deleteurl . '" class="btn btn-secondary">
        ' . get_string('forget', 'block_trax_xapi') . '
        </a>
        <a class="btn btn-primary" href="' . $returnurl . '">'.get_string('back', 'block_trax_xapi').'</a>
    </div>
';

// Fetch data.

$method = 'get_' . $source . '_logs';
$errors = errors::$method($lrs);

// Table setup.

$table = new flexible_table('global-errors');

$table->define_columns(['timestamp', 'course', 'code', 'template']);
$table->define_headers([
    get_string('timestamp', 'block_trax_xapi'),
    get_string('course', 'block_trax_xapi'),
    get_string('type', 'block_trax_xapi'),
    get_string('template', 'block_trax_xapi')
]);
$table->define_baseurl($baseurl);

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'global-errors');
$table->set_attribute('class', 'generaltable generalbox');

$table->setup();

// Table content.

foreach($errors as $error) {
    $url = new moodle_url('/blocks/trax_xapi/views/course_status.php', [
        'courseid' => $error->courseid,
        'lrs' => $lrs,
    ]);
    $course = "<a href='$url'>" . $error->coursename . "</a>";
    $timestamp = userdate($error->timestamp, "%d/%m/%Y at %H:%M");
    $code = get_string('modeling_error_code_' . $error->error, 'block_trax_xapi');
    $template = json_decode($error->data)->template;
    $table->add_data([$timestamp, $course, $code, $template]);
}
$table->print_html();

// Links.

echo '
    <div class="mb-3 mt-3">
        <a href="' . $retryurl . '" class="btn btn-secondary">
        ' . get_string('retry', 'block_trax_xapi') . '
        </a>
        <a href="' . $deleteurl . '" class="btn btn-secondary">
        ' . get_string('forget', 'block_trax_xapi') . '
        </a>
        <a class="btn btn-primary" href="' . $returnurl . '">'.get_string('back', 'block_trax_xapi').'</a>
    </div>
';

echo $OUTPUT->footer();
