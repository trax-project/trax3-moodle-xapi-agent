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
 * @package    block_trax_xapi_agent
 * @copyright  2024 Sébastien Fraysse <sebastien@fraysse.eu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_trax_xapi_agent\repositories;

defined('MOODLE_INTERNAL') || die();

use block_trax_xapi_agent\utils;

abstract class repository {

    /**
     * DB table.
     *
     * @var string $table
     */
    protected $table;

    /**
     * Get an entry from the DB, given a Moodle ID and type.
     *
     * @param int $mid Moodle ID of the item
     * @param string $type Type of item
     * @return stdClass|false
     */
    public function get_db_entry(int $mid, string $type) {
        global $DB;
        return $DB->get_record($this->table, [
            'mid' => $mid,
            'type' => $type,
        ]);
    }

    /**
     * Get an entry from the DB, and rise an exception if the entry does not exist.
     *
     * @param int $mid Moodle ID of the item
     * @param string $type Type of item
     * @return stdClass
     * @throws \moodle_exception
     */
    public function get_db_entry_or_fail(int $mid, string $type) {
        $entry = $this->get_db_entry($mid, $type);
        if (!$entry) {
            throw new \moodle_exception('exception_entry_not_found', 'block_trax_xapi_agent');
        }
        return $entry;
    }

    /**
     * Get an entry from the DB, given a UUID.
     *
     * @param string $uuid UUID of actor
     * @return stdClass|false
     */
    public function get_db_entry_by_uuid(string $uuid) {
        global $DB;
        return $DB->get_record($this->table, [
            'uuid' => $uuid,
        ]);
    }

    /**
     * Get an entry from the DB, and rise an exception if the entry does not exist.
     *
     * @param string $uuid UUID of actor
     * @return stdClass
     * @throws \moodle_exception
     */
    public function get_db_entry_by_uuid_or_fail(string $uuid) {
        $entry = $this->get_db_entry_by_uuid($uuid);
        if (!$entry) {
            throw new \moodle_exception('exception_entry_not_found', 'block_trax_xapi_agent');
        }
        return $entry;
    }

    /**
     * Get an entry from the DB, or create it if it does not exist.
     *
     * @param int $mid Moodle ID of the item
     * @param string $type Type of item
     * @param array $data More data
     * @return \stdClass
     */
    public function get_or_create_db_entry(int $mid, string $type, array $data = []) {
        global $DB;
        $entry = $this->get_db_entry($mid, $type);
        if (!$entry) {
            $entry = (object)array_merge([
                'mid' => $mid,
                'type' => $type,
                'uuid' => utils::uuid(),
            ], $data);
            $entry->id = $DB->insert_record($this->table, $entry);
        }
        return $entry;
    }

    /**
     * Update a DB entry.
     *
     * @param \stdClass $entry
     * @return void
     */
    public function update_db_entry(\stdClass $entry) {
        global $DB;
        $DB->update_record($this->table, $entry);
    }
}
