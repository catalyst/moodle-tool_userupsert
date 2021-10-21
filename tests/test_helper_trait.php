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
 * Tests helper.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userupsert\tests;


defined('MOODLE_INTERNAL') || die();

/**
 * Tests helper.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait test_helper_trait {

    /**
     * A helper function to create a custom profile field.
     *
     * @param string $shortname Short name of the field.
     * @param string $datatype Type of the field, e.g. text, checkbox, datetime, menu and etc.
     * @param bool $unique Should the field to be unique?
     *
     * @return \stdClass
     */
    protected function add_user_profile_field(string $shortname, string $datatype, bool $unique = false): \stdClass {
        global $DB;

        // Create a new profile field.
        $data = new \stdClass();
        $data->shortname = $shortname;
        $data->datatype = $datatype;
        $data->name = 'Test ' . $shortname;
        $data->description = 'This is a test field';
        $data->required = false;
        $data->locked = false;
        $data->forceunique = $unique;
        $data->signup = false;
        $data->visible = '0';
        $data->categoryid = '0';

        $DB->insert_record('user_info_field', $data);

        return $data;
    }

    /**
     * Set test config data.
     */
    protected function set_test_config_data() {
        $settings = <<<SETTING
UserName| Username policy is defined in Moodle security config
FirstName | The first name(s) of the user
LastName | The family name of the user
Email | A valid and unique email address
Auth | Auth plugins include manual, ldap, etc. Default is "manual"
Password | Plain text password consisting of any characters
Status | User status. Either active, deleted or suspended
CustomField | User custom field
SETTING;

        set_config('webservicefields', $settings, 'tool_userupsert');
        set_config('data_map_username', 'UserName', 'tool_userupsert');
        set_config('data_map_firstname', 'FirstName', 'tool_userupsert');
        set_config('data_map_lastname', 'LastName', 'tool_userupsert');
        set_config('data_map_email', 'Email', 'tool_userupsert');
        set_config('data_map_auth', 'Auth', 'tool_userupsert');
        set_config('data_map_password', 'Password', 'tool_userupsert');
        set_config('data_map_status', 'Status', 'tool_userupsert');
        set_config('data_map_profile_field_newfield', 'CustomField', 'tool_userupsert');

        set_config('usermatchfield', 'username', 'tool_userupsert');
    }

    /**
     * A helper method to get a dummy web service data.
     *
     * @return array
     */
    protected function get_web_service_data(): array {
        $data = [];

        foreach ($this->config->get_data_mapping() as $wsfield) {
            $data[$wsfield] = 'Test';
        }

        $data[$this->config->get_data_mapping()['username']] = 'test';
        $data[$this->config->get_data_mapping()['email']] = 'test@test.com';
        $data[$this->config->get_data_mapping()['status']] = 'active';
        $data[$this->config->get_data_mapping()['password']] = 'nhy6^YHN';
        $data[$this->config->get_data_mapping()['auth']] = 'manual';

        return $data;
    }

}
