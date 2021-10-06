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
 * Web Service functions.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userupsert\external;

defined('MOODLE_INTERNAL') || die();

use tool_userupsert\config;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use external_warnings;

/**
 * Web Service functions.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upsert extends external_api {

    /**
     * Define the parameters schema for the upsert_user function.
     *
     * @return external_function_parameters
     */
    public static function upsert_users_parameters(): external_function_parameters {
        $config = new config();
        $userfields = [];

        foreach ($config->get_fields() as $field => $description) {
            $userfields[$field] = new external_value(PARAM_RAW, $description);
        }

        return new external_function_parameters([
            'users' => new external_multiple_structure(new external_single_structure($userfields))
        ]);
    }

    /**
     * Upserts users.
     *
     * @param array $users A list of user fields.
     * @return array
     */
    public static function upsert_users(array $users): array {
        require_capability('tool/userupsert:upsert', \context_system::instance());

        $warnings = [];
        $warnings[] = [
            'item' => 'user',
            'itemid' => '',
            'warningcode' => '',
            'message' => '',
        ];

        return $warnings;
    }

    /**
     * Return values for upsert_user external function.
     *
     * @return external_warnings
     */
    public static function upsert_users_returns(): external_warnings {
        return new external_warnings('Item is always \'user\'');
    }

}
