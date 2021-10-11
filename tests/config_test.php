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
 * Tests for config class.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userupsert\tests;

use advanced_testcase;
use tool_userupsert\config;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for config class.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings_test extends advanced_testcase {

    /**
     * A helper function to create a custom profile field.
     *
     * @param string $shortname Short name of the field.
     * @param string $datatype Type of the field, e.g. text, checkbox, datetime, menu and etc.
     * @param bool $unique Should the field to be unique?
     *
     * @return \stdClass
     */
    protected function add_user_profile_field(string $shortname, string $datatype, bool $unique = false) : \stdClass {
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
     * Test class constants.
     */
    public function test_constants() {
        $this->assertSame(['username', 'idnumber', 'email'], config::MATCH_FIELDS_FROM_USER_TABLE);
        $this->assertSame(['text'], config::SUPPORTED_TYPES_OF_PROFILE_FIELDS);
        $this->assertSame('profile_field_', config::PROFILE_FIELD_PREFIX);
    }

    /**
     * Test get webservicefields when empty config.
     */
    public function test_get_webservicefields_empty_config() {
        $config = new config();
        $this->assertEmpty($config->get_web_service_fields());
    }

    /**
     * Test get webservicefields.
     */
    public function test_get_webservicefields() {
        $this->resetAfterTest();

        $testsetting = <<<SETTING
field1| Description 1
field2 |Description 2
field3 | Description 3
field 4 | Description 4
 | Description 5
field6 |
field7 | Description 7 | field8 | Description 8
SETTING;
        set_config('webservicefields', $testsetting, 'tool_userupsert');

        $config = new config();
        $expected = [
            'field1' => 'Description 1',
            'field2' => 'Description 2',
            'field3' => 'Description 3',
        ];

        $this->assertSame($expected, $config->get_web_service_fields());
    }

    /**
     * Test supported match fields without custom profile fields.
     */
    public function test_get_supported_match_fields_without_profile_fields() {
        $config = new config();

        $expected = [
            'username' => 'Username',
            'idnumber' => 'ID number',
            'email' => 'Email address',
        ];

        $this->assertSame($expected, $config->get_supported_match_fields());
    }

    /**
     * Test supported match fields with custom profile fields.
     */
    public function test_get_supported_match_fields_with_profile_fields() {
        $this->resetAfterTest();

        $config = new config();

        // Create bunch of profile fields.
        $this->add_user_profile_field('text1', 'text', true);
        $this->add_user_profile_field('checkbox1', 'checkbox', true);
        $this->add_user_profile_field('checkbox2', 'checkbox');
        $this->add_user_profile_field('text2', 'text', false);
        $this->add_user_profile_field('datetime1', 'datetime');
        $this->add_user_profile_field('menu1', 'menu');
        $this->add_user_profile_field('textarea1', 'textarea');
        $this->add_user_profile_field('text3', 'text', true);

        $userfields = [
            'username' => 'Username',
            'idnumber' => 'ID number',
            'email' => 'Email address',
        ];

        $profilefields = [
            'profile_field_text1' => 'Test text1',
            'profile_field_text3' => 'Test text3'
        ];
        $expected = array_merge($userfields, $profilefields);
        $this->assertSame($expected, $config->get_supported_match_fields());
    }

    /**
     * Test mandatory fields.
     */
    public function test_get_mandatory_fields() {
        $this->resetAfterTest();
        $config = new config();

        $expected = ['username', 'lastname', 'firstname', 'email', 'status'];
        $this->assertSame($expected, $config->get_mandatory_fields());

        set_config('usermatchfield', 'lastname', 'tool_userupsert');
        $config = new config();

        $this->assertSame($expected, $config->get_mandatory_fields());

        set_config('usermatchfield', 'test', 'tool_userupsert');
        $config = new config();

        $expected[] = 'test';
        $this->assertSame($expected, $config->get_mandatory_fields());
    }

    /**
     * Test user match field.
     */
    public function test_get_user_match_field() {
        $this->resetAfterTest();

        $config = new config();
        $this->assertSame('username', $config->get_user_match_field());

        set_config('usermatchfield', 'lastname', 'tool_userupsert');
        $config = new config();
        $this->assertSame('lastname', $config->get_user_match_field());
    }

    /**
     * Test mapping.
     */
    public function test_get_data_mapping() {
        $this->resetAfterTest();

        $config = new config();
        $this->assertEmpty($config->get_data_mapping());

        set_config('data_map_lastname', 'test_lastname', 'tool_userupsert');
        set_config('data_map_firstname', 'test_firstname', 'tool_userupsert');
        set_config('data_map_username', '', 'tool_userupsert');
        set_config('data_map_profile_field_custom', 'test_custom_field', 'tool_userupsert');

        $config = new config();
        $datamapping = $config->get_data_mapping();

        $this->assertTrue(key_exists('lastname', $datamapping));
        $this->assertTrue(key_exists('firstname', $datamapping));
        $this->assertTrue(key_exists('profile_field_custom', $datamapping));
        $this->assertFalse(key_exists('username', $datamapping));

        $this->assertSame('test_lastname', $datamapping['lastname']);
        $this->assertSame('test_firstname', $datamapping['firstname']);
        $this->assertSame('test_custom_field', $datamapping['profile_field_custom']);
    }

    /**
     * Test we can check that the config is ready.
     */
    public function test_is_ready() {
        $this->resetAfterTest();

        // Nothing configured.
        $config = new config();
        $this->assertFalse($config->is_ready());

        // Configure WS fields.
        set_config('webservicefields', 'field1 | Description 1', 'tool_userupsert');
        $config = new config();
        $this->assertFalse($config->is_ready());

        // Map matching field.
        set_config('data_map_username', 'field1', 'tool_userupsert');
        $config = new config();
        $this->assertFalse($config->is_ready());

        // Map all mandatory field.
        set_config('data_map_username', 'field1', 'tool_userupsert');
        set_config('data_map_lastname', 'field1', 'tool_userupsert');
        set_config('data_map_firstname', 'field1', 'tool_userupsert');
        set_config('data_map_email', 'field1', 'tool_userupsert');
        set_config('data_map_status', 'field1', 'tool_userupsert');
        $config = new config();
        $this->assertTrue($config->is_ready());

        // Now change matching field to something not actually mapped.
        set_config('usermatchfield', 'test', 'tool_userupsert');
        $config = new config();
        $this->assertFalse($config->is_ready());

        // And now map this field.
        set_config('data_map_test', 'field1', 'tool_userupsert');
        $config = new config();
        $this->assertTrue($config->is_ready());

        // Rename WS field so all mapping will break.
        set_config('webservicefields', 'field2 | Description 1', 'tool_userupsert');
        $config = new config();
        $this->assertFalse($config->is_ready());
    }

    /**
     * Test get get_default_auth when empty config.
     */
    public function test_get_default_auth_empty_config() {
        $config = new config();
        $this->assertSame('manual', $config->get_default_auth());
    }

    /**
     * Test get get_default_auth.
     */
    public function test_get_get_default_auth() {
        $this->resetAfterTest();
        set_config('defaultauth', 'test', 'tool_userupsert');

        $config = new config();
        $this->assertSame('test', $config->get_default_auth());
    }

}
