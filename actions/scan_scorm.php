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

use block_trax_xapi\sources\scorm\scanner as ScormScanner;
use block_trax_xapi\config;

require('../../../config.php');
require_login();
config::require_dev_tools();

// URL params.

$lrs = optional_param('lrs', null, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);
$returnurl = required_param('returnurl', PARAM_URL);

// Page setup.

config::require_admin();

// Action.

ScormScanner::run($lrs, $courseid);

redirect($returnurl);
