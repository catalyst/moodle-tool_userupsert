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
 * Tests for profile_fields class.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userupsert\tests;

use advanced_testcase;
use tool_userupsert\profile_fields;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/test_helper_trait.php');

/**
 * Tests for profile_fields class.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_fields_test extends advanced_testcase {
    use test_helper_trait;

    /**
     * Test class constants.
     */
    public function test_constants() {
        $this->assertSame(['username', 'idnumber', 'email'], profile_fields::MATCH_FIELDS_FROM_USER_TABLE);
        $this->assertSame(['text'], profile_fields::SUPPORTED_TYPES_OF_PROFILE_FIELDS);
        $this->assertSame('profile_field_', profile_fields::PROFILE_FIELD_PREFIX);
    }

    /**
     * Test supported match fields without custom profile fields.
     */
    public function test_get_supported_match_fields_without_profile_fields() {
        $expected = [
            'username' => 'Username',
            'idnumber' => 'ID number',
            'email' => 'Email address',
        ];

        $this->assertSame($expected, profile_fields::get_supported_match_fields());
    }

    /**
     * Test supported match fields with custom profile fields.
     */
    public function test_get_supported_match_fields_with_profile_fields() {
        $this->resetAfterTest();

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
        $this->assertSame($expected, profile_fields::get_supported_match_fields());
    }

    /**
     * Test data for self::test_is_custom_profile_field().
     * @return array
     */
    public function is_custom_profile_field_data_provider(): array {
        return [
            ['profile_field_test', true],
            ['profiletest', false],
            ['profile', false],
            ['profile_field_', true],
            ['profile_field_profile_field_', true],
            ['Test', false],
            [0, false],
            [false, false],
        ];
    }

    /**
     * Test that we can find put if the field is a custom profile field.
     *
     * @dataProvider is_custom_profile_field_data_provider
     *
     * @param mixed $value Test value.
     * @param bool $expected Expected value.
     */
    public function test_is_custom_profile_field($value, bool $expected) {
        $this->assertSame($expected, profile_fields::is_custom_profile_field($value));
    }

    /**
     * Test data for self::test_get_short_name().
     * @return array
     */
    public function get_short_name_data_provider(): array {
        return [
            ['profile_field_test', 'test'],
            ['profile_field_profile_field_test', 'profile_field_test'],
            ['test', 'test'],
            ['profile_field_', ''],
        ];
    }

    /**
     * Test that we can get field shortname from the profile field name.
     *
     * @dataProvider get_short_name_data_provider
     *
     * @param string $value Test value.
     * @param string $expected Expected value.
     */
    public function test_get_short_name(string $value, string $expected) {
        $this->assertSame($expected, profile_fields::get_field_short_name($value));
    }

    /**
     * Test prefixing a custom profile field.
     */
    public function test_prefix_prefix_custom_profile_field() {
        $this->assertSame(profile_fields::PROFILE_FIELD_PREFIX . 'test', profile_fields::prefix_custom_profile_field('test'));
    }

    /**
     * Test getting all profile fields.
     */
    public function test_get_profile_fields() {
        $this->resetAfterTest();

        $this->add_user_profile_field('text1', 'text', true);
        $this->add_user_profile_field('checkbox1', 'checkbox', true);
        $this->add_user_profile_field('checkbox2', 'checkbox');
        $this->add_user_profile_field('text2', 'text');

        $expected = [
            'username' => get_string('username'),
            'firstname' => get_string('firstname'),
            'lastname' => get_string('lastname'),
            'email' => get_string('email'),
            'city' => get_string('city'),
            'country' => get_string('country'),
            'lang' => get_string('language'),
            'description' => get_string('description'),
            'idnumber' => get_string('idnumber'),
            'institution' => get_string('institution'),
            'department' => get_string('department'),
            'phone1' => get_string('phone1'),
            'phone2' => get_string('phone2'),
            'address' => get_string('address'),
            'firstnamephonetic' => get_string('firstnamephonetic'),
            'lastnamephonetic' => get_string('lastnamephonetic'),
            'middlename' => get_string('middlename'),
            'alternatename' => get_string('alternatename'),
            'auth' => get_string('auth', 'tool_userupsert'),
            'password' => get_string('password'),
            'status' => get_string('status', 'tool_userupsert'),
            'profile_field_text1' => 'Test text1',
            'profile_field_checkbox1' => 'Test checkbox1',
            'profile_field_checkbox2' => 'Test checkbox2',
            'profile_field_text2' => 'Test text2',
        ];

        $this->assertSame($expected, profile_fields::get_profile_fields());
    }

}
