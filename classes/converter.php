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
     * Process a list of events.
     *
     * @param array $events
     * @param int $lrsnum
     * @return array
     */
    public static function convert_events(array $events, int $lrsnum) {
        
        // Statements modeling.
        $models = array_map(function ($event) {
            // TODO we should store all the modeler instances in a cache.

            // Determine the modeler name.
            if (str_contains($event->eventname, 'course_module_viewed')) {
                // We use a single modeler for all the modulename_course_module_viewed.
                $modelerName = '\core\event\course_module_viewed';
            } else {
                $modelerName = $event->eventname;
            }

            // Find the modeler class.
            $modelerClass = '';
            if (config::custom_modelers_namespace()) {
                // Custom modeler class.
                $modelerClass = config::custom_modelers_namespace() . $modelerName;
            }
            if (empty($modelerClass) || !class_exists($modelerClass)) {
                // Default modeler class.
                $modelerClass = '\block_trax_xapi\modelers' . $modelerName;
            }

            return (new $modelerClass)->statement($event);
        }, $events);

        // Filter the statements because the modelers may return errors.
        $models = array_filter($models, function ($model) use ($lrsnum) {
            // Log the error.
            if ($model->error && $model->error !== modeler::ERROR_IGNORE) {
                logger::log_modeling_error($lrsnum, $model->event, $model->error, isset($model->exception) ? $model->exception : null);
            }
            return !$model->error;
        });

        // Keep only the statements, not the errors.
        $statements = array_map(function ($statement) {
            return $statement->statement;
        }, $models);

        // Keys were preserved so we rearrange the keys.
        return array_values($statements);
    }
}
