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

namespace block_trax_xapi;

defined('MOODLE_INTERNAL') || die();

use block_trax_xapi\config;
use block_trax_xapi\modelers\base as modeler;

class converter {

    /**
     * Event source.
     */
    const SOURCE_EVENT = 1;

    /**
     * SCORM source.
     */
    const SOURCE_SCORM = 2;

    /**
     * Process a list of events.
     *
     * @param array $events
     * @param int $lrsnum
     * @param int $courseid
     * @return array
     */
    public static function convert_events(array $events, int $lrsnum, int $courseid) {
        
        // Statements modeling.
        $feedbacks = array_map(function ($event) {
            // TODO we should store all the modeler instances in a cache.

            // Determine the modeler name.
            if (str_contains($event->eventname, 'course_module_viewed')) {
                // We use a single modeler for all the modulename_course_module_viewed.
                $modelerName = '\core\event\course_module_viewed';
            } else {
                $modelerName = $event->eventname;
            }
            return self::statement($modelerName, $event);

        }, $events);

        return self::final_statements($feedbacks, self::SOURCE_EVENT, $lrsnum, $courseid);
    }

    /**
     * Process a list of SCORM attempts.
     *
     * @param array $attempts
     * @param string $eventname
     * @param int $lrsnum
     * @param int $courseid
     * @return array
     */
    public static function convert_scorm_attempts(array $attempts, string $eventname, int $lrsnum, int $courseid) {
        
        // Statements modeling.
        $feedbacks = array_map(function ($attempt) use ($eventname) {
            return self::statement('\scorm\\sco_' . $eventname, $attempt);
        }, $attempts);

        return self::final_statements($feedbacks, self::SOURCE_SCORM, $lrsnum, $courseid, $eventname);
    }

    /**
     * Process a list of SCORM interactions.
     *
     * @param object $attempt
     * @param array $interactions
     * @param string $template
     * @param int $lrsnum
     * @param int $courseid
     * @return array
     */
    public static function convert_scorm_interactions(object $attempt, array $interactions, int $lrsnum, int $courseid) {
        
        // Statements modeling.
        $feedbacks = array_map(function ($interaction) use ($attempt) {
            return self::statement('\scorm\\sco_interacted', $attempt, $interaction);
        }, $interactions);

        return self::final_statements($feedbacks, self::SOURCE_SCORM, $lrsnum, $courseid, 'interacted');
    }

    /**
     * Process a list of SCORM interactions.
     *
     * @param string $modelerName
     * @param \core\event\base|object $data
     * @param mixed $optdata
     * @return object
     */
    protected static function statement(string $modelerName, $data, $optdata = null) {
        $modelerClass = '';
        if (config::custom_modelers_namespace()) {
            // Custom modeler class.
            $modelerClass = config::custom_modelers_namespace() . $modelerName;
        }
        if (empty($modelerClass) || !class_exists($modelerClass)) {
            // Default modeler class.
            $modelerClass = '\block_trax_xapi\modelers' . $modelerName;
        }
        $modeler = new $modelerClass;
        if (!class_exists($modelerClass)) {
            return (object)['error' => $modeler::ERROR_MODELER_FILE, 'source' => $data, 'optsource' => $optdata];
        }
        return (new $modelerClass)->statement($data, $optdata);
    }

    /**
     * Get the final list of statements.
     *
     * @param array $feedbacks
     * @param int $source
     * @param int $lrsnum
     * @param int $courseid
     * @param string $eventname
     * @return array
     */
    protected static function final_statements(array $feedbacks, int $source, int $lrsnum, int $courseid, string $eventname = null) {
        // Filter the statements because the modelers may return errors.
        $feedbacks = array_filter($feedbacks, function ($feedback) use ($source, $lrsnum, $courseid, $eventname) {
            // Log the error.
            if ($feedback->error && $feedback->error !== modeler::ERROR_IGNORE) {
                errors::log_modeling_error(
                    $source,
                    $lrsnum,
                    $courseid,
                    $feedback->source,
                    $feedback->optsource,
                    isset($eventname) ? $eventname : '',
                    $feedback->template,
                    $feedback->error,
                    isset($feedback->exception) ? $feedback->exception : null
                );
            }
            return !$feedback->error;
        });

        // Keep only the statements, not the errors.
        $statements = array_map(function ($statement) {
            return $statement->statement;
        }, $feedbacks);

        // Keys were preserved so we rearrange the keys.
        return array_values($statements);
    }
}
