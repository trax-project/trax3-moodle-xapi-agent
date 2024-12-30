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

namespace block_trax_xapi\modelers;

defined('MOODLE_INTERNAL') || die();

use block_trax_xapi\config;
use block_trax_xapi\utils;
use block_trax_xapi\repositories\repo;
use block_trax_xapi\exceptions\ignore_event_exception;

abstract class base {

    /**
     * No error.
     */
    const ERROR_NO = 0;

    /**
     * Event ignored by the modeler.
     */
    const ERROR_IGNORE = 1;

    /**
     * Modeler file error.
     */
    const ERROR_MODELER_FILE = 2;

    /**
     * Template file error.
     */
    const ERROR_TEMPLATE_FILE = 3;

    /**
     * Template JSON parsing error.
     */
    const ERROR_TEMPLATE_JSON = 4;

    /**
     * Template JSON parsing error.
     */
    const ERROR_PLACEHOLDER = 5;

    /**
     * @var \core\event\base|object $event
     */
    protected $event;

    /**
     * @var array $event_other
     */
    protected $event_other;

    /**
     * User: xAPI data.
     *
     * @var array
     */
    protected $user_xapi;

    /**
     * Related user: xAPI data.
     *
     * @var array
     */
    protected $relateduser_xapi;

    /**
     * Context actor in xAPI format.
     *
     * @var array
     */
    protected $context_xapi;

    /**
     * Course props.
     *
     * @var object
     */
    protected $course_props;

    /**
     * System props.
     *
     * @var object
     */
    protected $system_props;

    /**
     * Context props.
     *
     * @var object
     */
    protected $context_props;

    /**
     * Get an xAPI statement, given a Moodle event.
     *
     * @param \core\event\base|object $event
     * @param mixed $optdata
     * @return object
     */
    public function statement($event, $optdata = null) {
        $this->event = $event;

        // Prepare the event other which must be an array.
        $this->event_other = [];
        // When it comes from an \core\event\base class, it's always an array.
        if (is_array($event->other)) {
            $this->event_other = $event->other;
        }
        // When it comes from a DB record, it may be an encoded JSON.
        // Some Moodle events have a textual 'null' value on 'other'!
        if (is_string($event->other) && $event->other != 'null') {
            $this->event_other = json_decode($event->other, true);
            // It may also be a serialized object.
            if (!$this->event_other) {
                $this->event_other = unserialize($event->other);
            }
        }

        // Get the right template.
        if (!$template = $this->template()) {
            return (object)['error' => self::ERROR_IGNORE, 'source' => $event, 'template' => null];
        }

        // Open the template.
        if (!$content = $this->templateContents($template)) {
            return (object)['error' => self::ERROR_TEMPLATE_FILE, 'source' => $event, 'template' => $template];
        }

        // Parse the JSON.
        if (!$json = json_decode($content, true)) {
            return (object)['error' => self::ERROR_TEMPLATE_JSON, 'source' => $event, 'template' => $template];
        }

        // Fill placeholders.
        try {
            $statement = $this->fill_placeholders($json);
        } catch (ignore_event_exception $e) {
            return (object)['error' => self::ERROR_IGNORE, 'source' => $event, 'template' => $template];
        } catch (\Exception $e) {
            return (object)['error' => self::ERROR_PLACEHOLDER, 'source' => $event, 'template' => $template, 'exception' => $e];
        }

        return (object)['error' => self::ERROR_NO, 'source' => $event, 'template' => $template, 'statement' => $statement];
    }

    /**
     * Get the JSON template location.
     *
     * @return string|false
     */
    protected function template() {
        return false;
    }

    /**
     * Get the JSON template content.
     *
     * @param string $template
     * @return string|false
     */
    protected function templateContents(string $template) {
        $templatePath = '';
        if (config::custom_templates_folder()) {
            // Custom template file.
            $templatePath = config::custom_templates_folder() . $template . '.json';
        }
        if (empty($templatePath) || !file_exists($templatePath)) {
            // Default template file.
            $templatePath = __DIR__ . '/../../templates/' . $template . '.json';
        }

        // Open the template.
        return file_get_contents($templatePath);
    }

    /**
     * Parse the template and fill the placeholders.
     *
     * @param array $template
     * @param string $template
     * @return array
     */
    protected function fill_placeholders(array $template, string $parentProp = null) {
        foreach ($template as $prop => $value) {
            if (is_array($value)) {
                $template[$prop] = $this->fill_placeholders($value, $prop);

                // Don't keep empty extensions.
                if ($prop == 'extensions' && empty($template[$prop])) {
                    unset($template[$prop]);
                }
            }
            if (is_string($value) && substr($value, 0, 1) == '%') {
                $method = str_replace(':', '_', substr($value, 1));
                if (substr($method, 0, 7) == 'modeler') {
                    $method = substr($method, 8);
                }
                if (!method_exists($this, $method)) {
                    throw new \Exception("A placeholder does not match with a known modeler method: $method.");
                }
                $template[$prop] = $this->$method();
                // Props with null values are removed.
                if (is_null($template[$prop])) {
                    unset($template[$prop]);
                }
            }
        }
        return $template;
    }

    /**
     * @return array
     */
    protected function user() {
        if (!isset($this->user_xapi)) {
            $this->user_xapi = repo::actors()->get_user($this->event->userid);
        }
        return $this->user_xapi;
    }

    /**
     * @return array
     */
    protected function relateduser() {
        if (!isset($this->relateduser_xapi)) {
            $this->relateduser_xapi = repo::actors()->get_user($this->event->relateduserid);
        }
        return $this->relateduser_xapi;
    }

    /**
     * @return void
     */
    protected function load_course() {
        if (!isset($this->course_props)) {
            $this->course_props = repo::activities()->get_course_props($this->event->courseid);
        }
    }

    /**
     * @return string
     */
    protected function course_iri() {
        $this->load_course();
        return $this->course_props->iri;
    }

    /**
     * @return string
     */
    protected function course_name() {
        $this->load_course();
        return $this->course_props->name;
    }

    /**
     * @return string
     */
    protected function course_component() {
        $this->load_course();
        return $this->course_props->component;
    }

    /**
     * @return string
     */
    protected function course_url() {
        $this->load_course();
        return $this->course_props->url;
    }

    /**
     * @return string
     */
    protected function course_idnumber() {
        $this->load_course();
        return $this->course_props->idnumber;
    }

    /**
     * @return void
     */
    protected function load_system() {
        if (!isset($this->system_props)) {
            $this->system_props = repo::activities()->get_system_props();
        }
    }

    /**
     * @return string
     */
    protected function system_iri() {
        $this->load_system();
        return $this->system_props->iri;
    }

    /**
     * @return void
     */
    protected function load_context() {
        if (!isset($this->context_xapi) && $this->event->contextlevel == 50) {
            $this->context_xapi = repo::actors()->get_user($this->event->contextinstanceid);
        } 
        if (!isset($this->context_props) && $this->event->contextlevel != 50) {
            $this->context_props = repo::activities()->get_context_props($this->event->contextid);
        }
    }

    /**
     * @return string
     */
    protected function context_iri() {
        $this->load_context();
        return $this->context_props->iri;
    }

    /**
     * @return string
     */
    protected function context_name() {
        $this->load_context();
        return $this->context_props->name;
    }

    /**
     * @return string
     */
    protected function context_component() {
        $this->load_context();
        return $this->context_props->component;
    }

    /**
     * @return string
     */
    protected function context_url() {
        $this->load_context();
        return $this->context_props->url;
    }

    /**
     * @return string
     */
    protected function context_idnumber() {
        $this->load_context();
        return $this->context_props->idnumber;
    }

    /**
     * @return string
     */
    protected function timestamp() {
        return utils::timestamp($this->event->timecreated);
    }
}
