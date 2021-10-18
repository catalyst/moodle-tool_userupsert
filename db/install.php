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
 * Installation for tool_userupsert.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install hook.
 */
function xmldb_tool_userupsert_install() {
    // Don't set default config during tests.
    if ((!defined('PHPUNIT_TEST') || !PHPUNIT_TEST)) {
        $defaultsetting = <<<SETTING
username| Username policy is defined in Moodle security config
firstname | The first name(s) of the user
lastname | The family name of the user
email | A valid and unique email address
auth | Auth plugins include manual, ldap, etc. Default is "manual"
password | Plain text password consisting of any characters
status | User status. Either active, deleted or suspended
SETTING;

        set_config('webservicefields', $defaultsetting, 'tool_userupsert');
        set_config('data_map_username', 'username', 'tool_userupsert');
        set_config('data_map_firstname', 'firstname', 'tool_userupsert');
        set_config('data_map_lastname', 'lastname', 'tool_userupsert');
        set_config('data_map_email', 'email', 'tool_userupsert');
        set_config('data_map_auth', 'auth', 'tool_userupsert');
        set_config('data_map_password', 'password', 'tool_userupsert');
        set_config('data_map_status', 'status', 'tool_userupsert');
    }
}
