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
 * Test for user extractor class.
 *
 * @package    tool_userupsert
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userupsert\tests;

use advanced_testcase;
use tool_userupsert\more_than_one_user_found_exception;
use tool_userupsert\user_extractor;
use dml_read_exception;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/test_helper_trait.php');

/**
 * Test for user extractor class.
 *
 * @package    tool_userupsert
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_extractor_test extends advanced_testcase {
    use test_helper_trait;

    /**
     * Test we can extract users using fields from {user} table.
     */
    public function test_get_user_by_field_from_user_table() {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $actual = user_extractor::get_user('id', $user1->id);
        $this->assertNotNull($actual);
        $this->assertSame($user1->id, $actual->id);

        $actual = user_extractor::get_user('username', $user2->username);
        $this->assertNotNull($actual);
        $this->assertSame($user2->id, $actual->id);

        $actual = user_extractor::get_user('username', 'random string');
        $this->assertNull($actual);
    }

    /**
     * Test we can extract users using fields from {user} table.
     */
    public function test_get_user_by_field_from_user_table_deleted_user() {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        delete_user($user1);
        delete_user($user2);

        $actual = user_extractor::get_user('id', $user1->id);
        $this->assertNull($actual);

        $actual = user_extractor::get_user('username', $user2->username);
        $this->assertNull($actual);
    }

    /**
     * Test we can extract users using fields from {user} table.
     */
    public function test_get_user_by_field_from_user_table_field_does_not_exist() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $this->expectException(dml_read_exception::class);
        user_extractor::get_user('notexists', $user->username);
    }

    /**
     * Test we can extract users using fields from {user} table when multiple users found.
     */
    public function test_get_user_by_field_from_user_table_when_multiple_users_found() {
        $this->resetAfterTest();

        // Two users with empty idnumber.
        $user1 = $this->getDataGenerator()->create_user(['idnumber' => '1']);
        $user2 = $this->getDataGenerator()->create_user(['idnumber' => '1']);

        $this->expectException(more_than_one_user_found_exception::class);
        user_extractor::get_user('idnumber', '1');
    }

    /**
     * Test we can extract users using custom profile fields.
     */
    public function test_get_user_by_custom_profile_field() {
        $this->resetAfterTest();

        // Unique fields.
        $field1 = $this->add_user_profile_field('field1', 'text', true);
        $field2 = $this->add_user_profile_field('field2', 'text', true);

        $user1 = $this->getDataGenerator()->create_user();
        profile_save_data((object)['id' => $user1->id, 'profile_field_' . $field1->shortname => 'User 1 Field 1']);
        profile_save_data((object)['id' => $user1->id, 'profile_field_' . $field2->shortname => 'User 1 Field 2']);

        $user2 = $this->getDataGenerator()->create_user();
        profile_save_data((object)['id' => $user2->id, 'profile_field_' . $field1->shortname => 'User 2 Field 1']);
        profile_save_data((object)['id' => $user2->id, 'profile_field_' . $field2->shortname => 'User 2 Field 2']);

        // Should find users.
        $actual = user_extractor::get_user('profile_field_field1', 'User 1 Field 1');
        $this->assertNotNull($actual);
        $this->assertSame($user1->id, $actual->id);

        $actual = user_extractor::get_user('profile_field_field2', 'User 1 Field 2');
        $this->assertNotNull($actual);
        $this->assertSame($user1->id, $actual->id);

        $actual = user_extractor::get_user('profile_field_field1', 'User 2 Field 1');
        $this->assertNotNull($actual);
        $this->assertSame($user2->id, $actual->id);

        $actual = user_extractor::get_user('profile_field_field2', 'User 2 Field 2');
        $this->assertNotNull($actual);
        $this->assertSame($user2->id, $actual->id);

        // Shouldn't find users.
        $actual = user_extractor::get_user('profile_field_field1', 'User 3 Field 1');
        $this->assertNull($actual);

        $actual = user_extractor::get_user('profile_field_field3', 'User 3 Field 1');
        $this->assertNull($actual);
    }

    /**
     * Test we can extract users using custom profile fields when found multiple users.
     */
    public function test_get_user_by_custom_profile_field_when_multiple_users_found() {
        $this->resetAfterTest();

        // Non unique fields.
        $field1 = $this->add_user_profile_field('field1', 'text');
        $field2 = $this->add_user_profile_field('field2', 'text');

        $user1 = $this->getDataGenerator()->create_user();
        profile_save_data((object)['id' => $user1->id, 'profile_field_' . $field1->shortname => 'User 1 Field 1']);
        profile_save_data((object)['id' => $user1->id, 'profile_field_' . $field2->shortname => 'User 1 Field 2']);

        $user2 = $this->getDataGenerator()->create_user();
        profile_save_data((object)['id' => $user2->id, 'profile_field_' . $field1->shortname => 'User 1 Field 1']);
        profile_save_data((object)['id' => $user2->id, 'profile_field_' . $field2->shortname => 'User 1 Field 2']);

        $this->expectException(more_than_one_user_found_exception::class);
        user_extractor::get_user('profile_field_field1', 'User 1 Field 1');
    }

}
