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
 * Test for user manager class.
 *
 * @package    tool_userupsert
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userupsert\tests;

use advanced_testcase;
use tool_userupsert\config;
use tool_userupsert\upsert_not_configured_exception;
use tool_userupsert\upsert_failed_exception;
use tool_userupsert\user_manager;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/test_helper_trait.php');

/**
 * Test for user manager class.
 *
 * @package    tool_userupsert
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_manager_test extends advanced_testcase {
    use test_helper_trait;

    /**
     * Test config instance.
     * @var config
     */
    protected $config;

    /**
     * A helper method to build a user manager.
     *
     * @return \tool_userupsert\user_manager
     */
    protected function get_user_manager(): user_manager {
        return new user_manager(new config());
    }

    /**
     * Test exception thrown if the plugin is not configured.
     */
    public function test_exception_when_not_configured() {
        $this->expectException(upsert_not_configured_exception::class);
        $this->expectExceptionMessage('Upsert plugin is not configured');
        $usermanager = $this->get_user_manager();
    }

    /**
     * Data provider for testing test_exception_thrown_if_missing_mandatory_field.
     *
     * @return array
     */
    public function exception_thrown_if_missing_mandatory_field_data_provider(): array {
        $config = new config();

        $data = [];
        foreach ($config->get_mandatory_fields() as $field) {
            $data[] = [$field];
        }

        return $data;
    }

    /**
     * Test exception of missing mandatory field.
     *
     * @dataProvider exception_thrown_if_missing_mandatory_field_data_provider
     *
     * @param string $mandatoryfield Field for testing.
     */
    public function test_exception_thrown_if_missing_mandatory_field(string $mandatoryfield) {
        $this->resetAfterTest();
        $this->set_test_config_data();

        $this->config = new config();

        $data = $this->get_web_service_data();

        // Remove one of the mandatory fields.
        $fieldname = $this->config->get_data_mapping()[$mandatoryfield];
        unset($data[$fieldname]);

        $usermanager = $this->get_user_manager();

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Missing mandatory field ' . $fieldname);

        $usermanager->upsert_user($data);
    }

    /**
     * Test exception if missing user match field.
     */
    public function test_exception_thrown_if_missing_user_match_field() {
        $this->resetAfterTest();
        $this->set_test_config_data();

        $this->add_user_profile_field('custom_field', 'text', true);
        set_config('usermatchfield', 'profile_field_custom_field', 'tool_userupsert');
        set_config('data_map_profile_field_custom_field', 'CustomField', 'tool_userupsert');

        $this->config = new config();

        $data = $this->get_web_service_data();

        $this->assertTrue(key_exists('CustomField', $data));
        unset($data['CustomField']);

        $usermanager = $this->get_user_manager();

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Missing mandatory field CustomField');

        $usermanager->upsert_user($data);
    }

    /**
     * Test incorrect status.
     */
    public function test_incorrect_status() {
        $this->resetAfterTest();
        $this->set_test_config_data();
        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $user = $this->getDataGenerator()->create_user();

        $data = $this->get_web_service_data();
        $data[$this->config->get_data_mapping()['username']] = $user->username;
        $data[$this->config->get_data_mapping()['status']] = 'random';

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Invalid status: random');
        $usermanager->upsert_user($data);
    }

    /**
     * Test deleting existing user.
     */
    public function test_can_delete_user_existing_user() {
        global $DB;

        $this->resetAfterTest();
        $this->set_test_config_data();
        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $user = $this->getDataGenerator()->create_user();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['username']] = $user->username;
        $data[$this->config->get_data_mapping()['status']] = 'deleted';

        $this->assertTrue($DB->record_exists('user', ['id' => $user->id, 'deleted' => 0]));
        $this->assertFalse($DB->record_exists('user', ['id' => $user->id, 'deleted' => 1]));

        $usermanager->upsert_user($data);

        $this->assertFalse($DB->record_exists('user', ['id' => $user->id, 'deleted' => 0]));
        $this->assertTrue($DB->record_exists('user', ['id' => $user->id, 'deleted' => 1]));
    }

    /**
     * Make sure a new user is not created when status is deleted.
     */
    public function test_user_is_not_created_if_status_is_deleted() {
        global $DB;

        $this->resetAfterTest();
        $this->set_test_config_data();
        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['username']] = 'random';
        $data[$this->config->get_data_mapping()['status']] = 'deleted';

        $this->assertFalse($DB->record_exists('user', ['username' => 'random', 'deleted' => 0]));
        $this->assertFalse($DB->record_exists('user', ['username' => 'random', 'deleted' => 1]));

        $usermanager->upsert_user($data);

        $this->assertFalse($DB->record_exists('user', ['username' => 'random', 'deleted' => 0]));
        $this->assertFalse($DB->record_exists('user', ['username' => 'random', 'deleted' => 1]));
    }

    /**
     * Test invalid email when a new user.
     */
    public function test_exception_when_invalid_email_new_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['email']] = 'broken@email';

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Invalid email: broken@email');

        $usermanager->upsert_user($data);
    }

    /**
     * Test invalid email when an existing user.
     */
    public function test_exception_when_invalid_email_existing_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();

        $usermanager = $this->get_user_manager();
        $this->config = new config();
        $user = $this->getDataGenerator()->create_user();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['username']] = $user->username;
        $data[$this->config->get_data_mapping()['email']] = 'broken@email';

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Invalid email: broken@email');

        $usermanager->upsert_user($data);
    }

    /**
     * Test not allowed email for a new user.
     */
    public function test_exception_when_not_allowed_email_new_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();
        set_config('allowemailaddresses', 'example.com test.com');

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['email']] = 'notallowed@moodle.com';

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage(
            'Email is not allowed: notallowed@moodle.com. Error: This email is not one of those that are allowed (example.com test.com)'
        );

        $usermanager->upsert_user($data);
    }

    /**
     * Test not allowed email for an existing user.
     */
    public function test_exception_when_not_allowed_email_existing_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();
        set_config('allowemailaddresses', 'example.com test.com');

        $usermanager = $this->get_user_manager();
        $this->config = new config();
        $user = $this->getDataGenerator()->create_user();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['username']] = $user->username;
        $data[$this->config->get_data_mapping()['email']] = 'notallowed@moodle.com';

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage(
            'Email is not allowed: notallowed@moodle.com. Error: This email is not one of those that are allowed (example.com test.com)'
        );

        $usermanager->upsert_user($data);
    }

    /**
     * Test email taken for a new user.
     */
    public function test_exception_when_taken_email_new_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();

        $user = $this->getDataGenerator()->create_user();

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['email']] = $user->email;

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Email is already taken: ' . $user->email);

        $usermanager->upsert_user($data);
    }

    /**
     * Test email taken for an existing user.
     */
    public function test_exception_when_taken_email_existing_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();

        $user = $this->getDataGenerator()->create_user();
        $existinguser = $this->getDataGenerator()->create_user();

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['username']] = $existinguser->username;
        $data[$this->config->get_data_mapping()['email']] = $user->email;

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Email is already taken: ' . $user->email);

        $usermanager->upsert_user($data);
    }

    /**
     * Test email taken for a new user when allowed to have duplicate emails.
     */
    public function test_taken_email_when_duplicate_emails_are_allowed_new_user() {
        global $DB;

        $this->resetAfterTest();
        $this->set_test_config_data();
        set_config('allowaccountssameemail', true);

        $user = $this->getDataGenerator()->create_user();
        $this->assertSame(1, $DB->count_records('user', ['email' => $user->email]));

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();
        unset($data[$this->config->get_data_mapping()['password']]);
        $data[$this->config->get_data_mapping()['email']] = $user->email;

        $usermanager->upsert_user($data);

        $this->assertSame(2, $DB->count_records('user', ['email' => $user->email]));
    }

    /**
     * Test email taken for an existing user when allowed to have duplicate emails.
     */
    public function test_taken_email_when_duplicate_emails_are_allowed_existing_user() {
        global $DB;

        $this->resetAfterTest();
        $this->set_test_config_data();
        set_config('allowaccountssameemail', true);

        $user = $this->getDataGenerator()->create_user();
        $existinguser = $this->getDataGenerator()->create_user();

        $this->assertSame(1, $DB->count_records('user', ['email' => $user->email]));

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['username']] = $existinguser->username;
        unset($data[$this->config->get_data_mapping()['password']]);
        $data[$this->config->get_data_mapping()['email']] = $user->email;

        $usermanager->upsert_user($data);
        $this->assertSame(2, $DB->count_records('user', ['email' => $user->email]));
    }

    /**
     * Test username taken for a  new user.
     */
    public function test_exception_when_taken_username_new_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();
        set_config('usermatchfield', 'email', 'tool_userupsert');

        $usermanager = $this->get_user_manager();
        $this->config  = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['username']] = 'admin';

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Username is already taken: admin');

        $usermanager->upsert_user($data);
    }

    /**
     * Test user name taken for an existing user.
     */
    public function test_exception_when_taken_username_existing_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();
        set_config('usermatchfield', 'email', 'tool_userupsert');

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $existinguser = $this->getDataGenerator()->create_user();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['email']] = $existinguser->email;
        $data[$this->config->get_data_mapping()['username']] = 'admin';

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Username is already taken: admin');

        $usermanager->upsert_user($data);
    }

    /**
     * Test invalid auth for a new user.
     */
    public function test_exception_invalid_auth_new_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['auth']] = 'random';

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Invalid auth method: random');

        $usermanager->upsert_user($data);
    }

    /**
     * Test invalid auth for an existing user.
     */
    public function test_exception_invalid_auth_existing_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $existinguser = $this->getDataGenerator()->create_user();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['username']] = $existinguser->username;
        $data[$this->config->get_data_mapping()['auth']] = 'random';

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Invalid auth method: random');

        $usermanager->upsert_user($data);
    }

    /**
     * Test invalid username when a new user.
     */
    public function test_exception_invalid_username_when_new_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();
        set_config('usermatchfield', 'email', 'tool_userupsert');

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['username']] = 'Test';
        unset($data[$this->config->get_data_mapping()['password']]);

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Error updating profile fields. Error: The username must be in lower case (The username must be in lower case)');
        $usermanager->upsert_user($data);
    }

    /**
     * Test invalid username when an existing user.
     */
    public function test_exception_invalid_username_when_existing_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();
        set_config('usermatchfield', 'email', 'tool_userupsert');
        $existinguser = $this->getDataGenerator()->create_user();

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['username']] = 'Test';
        $data[$this->config->get_data_mapping()['email']] = $existinguser->email;
        unset($data[$this->config->get_data_mapping()['password']]);

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Error updating profile fields. Error: The username must be in lower case (The username must be in lower case)');
        $usermanager->upsert_user($data);
    }

    /**
     * Test invalid password when an existing user.
     */
    public function test_exception_invalid_password_when_new_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();
        set_config('usermatchfield', 'email', 'tool_userupsert');

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['password']] = 'weak';

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Error updating profile fields. Error: error/<div>Passwords must be at least 8 characters long.');
        $usermanager->upsert_user($data);
    }

    /**
     * Test invalid password when an existing user.
     */
    public function test_exception_invalid_password_when_existing_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();
        set_config('usermatchfield', 'email', 'tool_userupsert');
        $existinguser = $this->getDataGenerator()->create_user();

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['username']] = $existinguser->username;
        $data[$this->config->get_data_mapping()['email']] = $existinguser->email;
        $data[$this->config->get_data_mapping()['password']] = 'weak';

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Error updating profile fields. Error: error/<div>Passwords must be at least 8 characters long.');
        $usermanager->upsert_user($data);
    }

    /**
     * Test invalid custom profile fields for a new user.
     */
    public function test_exception_invalid_custom_profile_field_when_new_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();
        $customfield = $this->add_user_profile_field('newfield', 'text', true);
        set_config('data_map_profile_field_newfield', 'CustomField', 'tool_userupsert');

        $user = $this->getDataGenerator()->create_user();
        profile_save_data((object)['id' => $user->id, 'profile_field_' . $customfield->shortname => 'User Field']);

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['profile_field_newfield']] = 'User Field';
        unset($data[$this->config->get_data_mapping()['password']]);

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Error updating profile fields. Error: Error setting custom field data. Error: profile_field_newfield: This value has already been used.');

        $usermanager->upsert_user($data);
    }

    /**
     * Test invalid custom profile fields for an existing user.
     */
    public function test_exception_invalid_custom_profile_field_when_existing_user() {
        $this->resetAfterTest();
        $this->set_test_config_data();
        $customfield = $this->add_user_profile_field('newfield', 'text', true);
        set_config('data_map_profile_field_newfield', 'CustomField', 'tool_userupsert');

        $user = $this->getDataGenerator()->create_user();
        profile_save_data((object)['id' => $user->id, 'profile_field_' . $customfield->shortname => 'User 1 Field 1']);

        $existinguser = $this->getDataGenerator()->create_user();
        profile_save_data((object)['id' => $existinguser->id, 'profile_field_' . $customfield->shortname => 'User existing Field']);

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['username']] = $existinguser->username;
        $data[$this->config->get_data_mapping()['email']] = $existinguser->email;
        $data[$this->config->get_data_mapping()['profile_field_newfield']] = 'User existing Field';
        unset($data[$this->config->get_data_mapping()['password']]);

        $usermanager->upsert_user($data);

        $data[$this->config->get_data_mapping()['profile_field_newfield']] = 'User 1 Field 1';

        $this->expectException(upsert_failed_exception::class);
        $this->expectExceptionMessage('Error updating profile fields. Error: Error setting custom field data. Error: profile_field_newfield: This value has already been used.');
        $usermanager->upsert_user($data);
    }

    /**
     * Test that can set an empty password when creating a user.
     */
    public function test_creating_a_user_without_a_password() {
        $this->resetAfterTest();
        $this->set_test_config_data();

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        unset($data[$this->config->get_data_mapping()['password']]);

        $usermanager->upsert_user($data);
        $user = get_complete_user_data('username', 'test');
        $this->assertTrue(password_verify('', $user->password));
    }

    /**
     * Test that can set a password when updating a user.
     */
    public function test_updating_a_user_with_a_password() {
        $this->resetAfterTest();
        $this->set_test_config_data();

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['password']] = 'nhy6^YHN';

        $usermanager->upsert_user($data);
        $user = get_complete_user_data('username', 'test');
        $this->assertTrue(password_verify('nhy6^YHN', $user->password));

        $data[$this->config->get_data_mapping()['password']] = 'NHY^6yhn';

        $usermanager->upsert_user($data);
        $user = get_complete_user_data('username', 'test');
        $this->assertTrue(password_verify('NHY^6yhn', $user->password));
    }

    /**
     * Test status when creating a user.
     */
    public function test_status_when_a_new_user() {
        $this->resetAfterTest();

        $this->set_test_config_data();

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        // Active status.
        $data[$this->config->get_data_mapping()['username']] = 'newuser1';
        $data[$this->config->get_data_mapping()['email']] = 'newuser1@test.ru';
        unset($data[$this->config->get_data_mapping()['password']]);
        $data[$this->config->get_data_mapping()['status']] = 'active';

        $usermanager->upsert_user($data);
        $user = get_complete_user_data('username', 'newuser1');
        $this->assertEquals(0, $user->suspended);

        // Suspended status.
        $data[$this->config->get_data_mapping()['username']] = 'newuser2';
        $data[$this->config->get_data_mapping()['email']] = 'newuser2@test.ru';
        unset($data[$this->config->get_data_mapping()['password']]);
        $data[$this->config->get_data_mapping()['status']] = 'suspended';

        $usermanager->upsert_user($data);
        $user = get_complete_user_data('username', 'newuser2');
        $this->assertEquals(1, $user->suspended);

        // Deleted status.
        $data[$this->config->get_data_mapping()['username']] = 'newuser3';
        $data[$this->config->get_data_mapping()['email']] = 'newuser3@test.ru';
        unset($data[$this->config->get_data_mapping()['password']]);
        $data[$this->config->get_data_mapping()['status']] = 'deleted';

        $usermanager->upsert_user($data);
        $user = get_complete_user_data('username', 'newuser3');
        $this->assertFalse($user);
    }

    /**
     * Test status when updating a user.
     */
    public function test_status_when_existing_user() {
        $this->resetAfterTest();

        $this->set_test_config_data();

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $existinguser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $user = get_complete_user_data('username', $existinguser->username);
        $this->assertEquals(1, $user->suspended);

        $data = $this->get_web_service_data();

        // Active status.
        $data[$this->config->get_data_mapping()['username']] = $existinguser->username;
        $data[$this->config->get_data_mapping()['email']] = $existinguser->email;
        unset($data[$this->config->get_data_mapping()['password']]);

        $usermanager->upsert_user($data);
        $user = get_complete_user_data('username', $existinguser->username);
        $this->assertEquals(0, $user->suspended);

        // Suspended status.
        $data[$this->config->get_data_mapping()['status']] = 'suspended';

        $usermanager->upsert_user($data);
        $user = get_complete_user_data('username', $existinguser->username);
        $this->assertEquals(1, $user->suspended);

        // Deleted status.
        $data[$this->config->get_data_mapping()['status']] = 'deleted';

        $usermanager->upsert_user($data);
        $user = get_complete_user_data('username', $existinguser->username);
        $this->assertFalse($user);
    }

    /**
     * Test default auth is set.
     */
    public function test_default_auth_is_set() {
        $this->resetAfterTest();

        $this->set_test_config_data();
        set_config('defaultauth', 'nologin', 'tool_userupsert');

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $existinguser = $this->getDataGenerator()->create_user(['auth' => 'manual']);

        $user = get_complete_user_data('username', $existinguser->username);
        $this->assertEquals('manual', $user->auth);

        $data = $this->get_web_service_data();

        // Existing user auth shouldn't be set to default.
        $data[$this->config->get_data_mapping()['username']] = $existinguser->username;
        $data[$this->config->get_data_mapping()['email']] = $existinguser->email;
        unset($data[$this->config->get_data_mapping()['password']]);
        unset($data[$this->config->get_data_mapping()['auth']]);

        $usermanager->upsert_user($data);
        $user = get_complete_user_data('username', $existinguser->username);
        $this->assertEquals('manual', $user->auth);

        // New user should get a default configured auth.
        $data[$this->config->get_data_mapping()['username']] = 'newuser';
        $data[$this->config->get_data_mapping()['email']] = 'newuser@test.ru';

        $usermanager->upsert_user($data);
        $user = get_complete_user_data('username', 'newuser');
        $this->assertEquals('nologin', $user->auth);
    }

    /**
     * Test partly update user.
     */
    public function test_can_partly_update_when_existing_user() {
        $this->resetAfterTest();

        $this->set_test_config_data();

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $existinguser = $this->getDataGenerator()->create_user();
        $user = get_complete_user_data('username', $existinguser->username);
        $this->assertEquals(0, $user->suspended);

        // Update only email.
        $data = [];
        $data[$this->config->get_data_mapping()['username']] = $existinguser->username;
        $data[$this->config->get_data_mapping()['email']] = 'newemail@test.ru';

        $usermanager->upsert_user($data);
        $user = get_complete_user_data('username', $existinguser->username);
        $this->assertEquals(0, $user->suspended);
        $this->assertEquals('newemail@test.ru', $user->email);

        // Update email and suspend.
        $data = [];
        $data[$this->config->get_data_mapping()['username']] = $existinguser->username;
        $data[$this->config->get_data_mapping()['email']] = 'newemail2@test.ru';
        $data[$this->config->get_data_mapping()['status']] = 'suspended';

        $usermanager->upsert_user($data);
        $user = get_complete_user_data('username', $existinguser->username);
        $this->assertEquals('newemail2@test.ru', $user->email);
        $this->assertEquals(1, $user->suspended);

        // Deleted status.
        $data = [];
        $data[$this->config->get_data_mapping()['username']] = $existinguser->username;
        $data[$this->config->get_data_mapping()['status']] = 'deleted';

        $usermanager->upsert_user($data);
        $user = get_complete_user_data('username', $existinguser->username);
        $this->assertFalse($user);
    }

    /**
     * Test user record is not created if something goes wrong.
     */
    public function test_user_not_created_if_something_goes_wrong() {
        global $DB;

        $this->resetAfterTest();
        $this->preventResetByRollback();

        $this->set_test_config_data();
        $customfield = $this->add_user_profile_field('newfield', 'text', true);
        set_config('data_map_profile_field_newfield', 'CustomField', 'tool_userupsert');

        $user = $this->getDataGenerator()->create_user();
        profile_save_data((object)['id' => $user->id, 'profile_field_' . $customfield->shortname => 'User 1 Field 1']);

        $usermanager = $this->get_user_manager();
        $this->config = new config();

        $data = $this->get_web_service_data();

        $data[$this->config->get_data_mapping()['username']] = 'newuser';
        $data[$this->config->get_data_mapping()['email']] = 'newuser@mail.com';
        $data[$this->config->get_data_mapping()['profile_field_newfield']] = 'User 1 Field 1';
        unset($data[$this->config->get_data_mapping()['password']]);

        $this->assertFalse($DB->record_exists('user', ['username' => 'newuser']));

        try {
            $usermanager->upsert_user($data);
        } catch (\Exception $exception) {
            $this->assertFalse($DB->record_exists('user', ['username' => 'newuser']));
        }
    }

}
