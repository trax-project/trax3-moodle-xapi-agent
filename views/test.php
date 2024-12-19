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

use block_trax_xapi\sources\scorm\scanner;

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

$baseurl = new moodle_url('/blocks/trax_xapi/views/test.php', $urlparams);
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

echo '<p>No test :(</p>';

scanner::run();

// Return link.
echo '<div class="backlink mt-5">' . html_writer::link($returnurl, get_string('back')) . '</div>';

echo $OUTPUT->footer();
