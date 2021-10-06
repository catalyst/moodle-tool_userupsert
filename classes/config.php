<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Config class.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userupsert;

defined('MOODLE_INTERNAL') || die();

/**
 * Config class.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config {

    /**
     * A list of configured field and their descriptions.
     * @var array
     */
    private $fields = [];

    /**
     * Constructor.
     */
    public function __construct() {
        $config = get_config('tool_userupsert');

        if (!empty($config->fields)) {
            $fields = explode("\n", str_replace("\r\n", "\n", $config->fields));

            foreach ($fields as $fieldstring) {
                $field = new \stdClass();
                $parts = explode('|', $fieldstring);
                if (count($parts) === 2) {
                    $field->name = trim($parts[0]);
                    $field->description = trim($parts[1]);

                    if ($this->is_valid_field($field)) {
                        $this->fields[$field->name] = $field->description;
                    }
                }
            }
        }
    }

    /**
     * Return list of fields.
     *
     * @return array
     */
    public function get_fields(): array {
        return $this->fields;
    }

    /**
     * Check if the given field is valid.
     *
     * @param \stdClass $field Field to check.
     * @return bool
     */
    private function is_valid_field(\stdClass $field): bool {
        if (!property_exists($field, 'name') || !property_exists($field, 'description')) {
            return false;
        }

        return !empty($field->name) && !strpos($field->name, ' ') && !empty($field->description);
    }

}
