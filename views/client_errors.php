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

$lrs = required_param('lrs', PARAM_INT);
$returnurl = required_param('returnurl', PARAM_URL);

// Page setup.

$PAGE->set_context(null); // hack - set context to something, by default to system context
config::require_admin();

$urlparams = [
    'lrs' => $lrs,
    'returnurl' => $returnurl,
];

$baseurl = new moodle_url('/blocks/trax_xapi/views/client_errors.php', $urlparams);
$PAGE->set_url($baseurl);

$title = get_string('client_errors', 'block_trax_xapi');
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$PAGE->navbar->add(get_string('pluginname', 'block_trax_xapi'));
$PAGE->navbar->add(get_string('client_errors', 'block_trax_xapi'), $baseurl);
echo $OUTPUT->header();


// Fetch data.

$errors = errors::get_client_logs($lrs);

// Links.

$deleteurl = (new moodle_url("/blocks/trax_xapi/actions/delete_client_errors.php", $urlparams))->__toString();
$retryurl = (new moodle_url("/blocks/trax_xapi/actions/retry_client_errors.php", $urlparams))->__toString();

echo '
    <div class="mb-3 mt-3">
        <a href="' . $retryurl . '" class="btn btn-secondary">
        ' . get_string('retry', 'block_trax_xapi') . '
        </a>
        <a href="' . $deleteurl . '" class="btn btn-secondary">
        ' . get_string('forget', 'block_trax_xapi') . '
        </a>
        <a class="btn btn-primary" href="' . $returnurl . '">
        ' . get_string('back', 'block_trax_xapi') . '
        </a>
    </div>
';

// Table setup.

$table = new flexible_table('client-errors');

$table->define_columns(['timestamp', 'type']);
$table->define_headers([
    get_string('timestamp', 'block_trax_xapi'),
    get_string('type', 'block_trax_xapi'),
]);
$table->define_baseurl($baseurl);

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'client-errors');
$table->set_attribute('class', 'generaltable generalbox');

$table->column_class('timestamp', 'timestamp');
$table->column_class('type', 'type');

$table->setup();

// Table content.

foreach($errors as $error) {
    $timestamp = userdate($error->timestamp, "%d/%m/%Y at %H:%M");
    $type = $error->error ? $error->error : get_string('error_http', 'block_trax_xapi');
    $table->add_data([$timestamp, $type]);
}
$table->print_html();

// Delete errors.

echo '
    <div class="mb-3 mt-3">
        <a href="' . $retryurl . '" class="btn btn-secondary">
        ' . get_string('retry', 'block_trax_xapi') . '
        </a>
        <a href="' . $deleteurl . '" class="btn btn-secondary">
        ' . get_string('forget', 'block_trax_xapi') . '
        </a>
        <a class="btn btn-primary" href="' . $returnurl . '">
        ' . get_string('back', 'block_trax_xapi') . '
        </a>
    </div>
';

echo $OUTPUT->footer();
