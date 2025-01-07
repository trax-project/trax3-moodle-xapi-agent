<?php
// This file is part of the TRAX xAPI plugin for Moodle.
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

namespace block_trax_xapi\modelers\mod_h5pactivity\event;

defined('MOODLE_INTERNAL') || die();

use block_trax_xapi\modelers\base as modeler;
use core_xapi\iri;

class statement_received extends modeler {

    /**
     * Get an xAPI statement, given a Moodle event.
     *
     * @param \core\event\base|object $event
     * @param mixed $optdata
     * @return object
     */
    public function statement($event, $optdata = null) {
        $this->event = $event;

		$statement = $event->other;

		if (is_string($statement)) {
			$statement = json_decode($statement, true);
		}

		if (is_object($statement)) {
			$statement = json_decode(json_encode($statement), true);
		}

		// Be sure to have context activities.
		if (!isset($statement['context'])) {
			$statement['context'] = [];
		}
		if (!isset($statement['context']['contextActivities'])) {
			$statement['context']['contextActivities'] = [];
		}
		if (!isset($statement['context']['contextActivities']['parent'])) {
			$statement['context']['contextActivities']['parent'] = [];
		}
		if (!isset($statement['context']['contextActivities']['grouping'])) {
			$statement['context']['contextActivities']['grouping'] = [];
		}
		if (!isset($statement['context']['contextActivities']['category'])) {
			$statement['context']['contextActivities']['category'] = [];
		}

		// Set the actor which is not defined by H5P.
		$statement['actor'] = $this->user();

		$h5pIri = iri::generate($event->contextid, 'activity');

		// When the object is the H5P activity.
		if ($statement['object']['id'] == $h5pIri) {
			$statement['object'] = $this->activity($statement['object']);
			$statement['context']['contextActivities']['parent'][] = $this->course();
		}

		// When the parent is the H5P activity.
		if (count($statement['context']['contextActivities']['parent']) > 0) {
			$statement['context']['contextActivities']['parent'][0] = $this->activity($statement['context']['contextActivities']['parent'][0], false);
		}

		// Add the VLE profile.
		$statement['context']['contextActivities']['category'][] = [
			'id' => 'https://w3id.org/xapi/vle',
			'definition' => [
				'type' => 'http://adlnet.gov/expapi/activities/profile'
			]
		];

		// Add the system IRI.
		$statement['context']['contextActivities']['grouping'][] = [
			'id' => $this->system_iri(),
			'definition' => [
				'type' => 'https://w3id.org/xapi/vle/activity-types/system'
			]
		];

        return (object)['error' => self::ERROR_NO, 'source' => $event, 'optsource' => $optdata, 'statement' => $statement];
	}

    /**
     * Get the xAPI activity for the H5P activity.
     *
     * @param array $original
     * @param bool $fulldef
     * @return array
     */
	protected function activity(array $original, bool $fulldef = true) {

		$activity = [
			'id' => $this->context_iri(),
			'definition' => [
				'type' => 'https://w3id.org/xapi/tla/activity-types/activity',
				'extensions' => [
					'https://w3id.org/xapi/vle/extensions/component' => $this->context_component(),
				]
			]
		];

		if ($fulldef) {
			$activity['objectType'] = 'Activity';
			$activity['definition']['name'] = $this->context_name();

			$originalExtensions = isset($original['extensions']) ? $original['extensions'] : [];
			$activity['definition']['extensions'] = array_merge([
				'https://w3id.org/xapi/vle/extensions/component' => $this->context_component(),
				'https://w3id.org/xapi/vle/extensions/url' => $this->context_url(),
			], $originalExtensions);
		}

		if ($this->context_idnumber()) {
			$activity['definition']['extensions']['https://w3id.org/xapi/vle/extensions/shared-id'] = $this->context_idnumber();
		}

		return $activity;
	}

    /**
     * Get the xAPI course.
     *
     * @param array $original
     * @param bool $fulldef
     * @return array
     */
	protected function course() {

		$course = [
			'id' => $this->course_iri(),
			'definition' => [
				'type' => 'https://w3id.org/xapi/tla/activity-types/content_set',
				'extensions' => [
					'https://w3id.org/xapi/vle/extensions/component' => 'course',
				]
			]
		];

		if ($this->course_idnumber()) {
			$course['definition']['extensions']['https://w3id.org/xapi/vle/extensions/shared-id'] = $this->course_idnumber();
		}

		return $course;
	}
}
