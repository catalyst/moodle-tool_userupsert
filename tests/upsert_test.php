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
 * Tests for upsert class.
 *
 * @package    tool_userupsert
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userupsert\tests;

use advanced_testcase;
use external_api;
use tool_userupsert\config;
use context_system;
use tool_userupsert\event\upsert_failed;
use tool_userupsert\event\upsert_succeeded;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/test_helper_trait.php');

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * Tests for upsert class.
 *
 * @package    tool_userupsert
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upsert_test extends advanced_testcase {
    use test_helper_trait;

    /**
     * Test config instance.
     * @var config
     */
    protected $config;

    /**
     * A helper method to verify web service error.
     *
     * @param array $response Response array.
     */
    protected function verify_error(array $response): void {
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('exception', $response);
        $this->assertTrue($response['error']);
    }

    /**
     * A helper method to verify web service success.
     *
     * @param array $response Response array.
     */
    protected function verify_success(array $response): void {
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertFalse($response['error']);
        $this->assertArrayNotHasKey('exception', $response);

        $this->assertArrayHasKey('data', $response);
    }

    /**
     * Test upsert without permissions.
     */
    public function test_upsert_without_permissions() {
        $this->resetAfterTest();
        $this->set_test_config_data();
        $this->config = new config();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Workaround to be able to call external_api::call_external_function.
        $_POST['sesskey'] = sesskey();

        $data = $this->get_web_service_data();
        $response = external_api::call_external_function('tool_userupsert_upsert_users', [
            'users' => [$data]
        ]);

        $this->verify_error($response);
        $this->assertSame(
            'Sorry, but you do not currently have permissions to do that (Upsert users).',
            $response['exception']->message
        );
    }

    /**
     * Test upsert with invalid params.
     */
    public function test_upsert_invalid_params() {
        $this->resetAfterTest();
        $this->set_test_config_data();
        $this->config = new config();
        $this->setAdminUser();

        // Workaround to be able to call external_api::call_external_function.
        $_POST['sesskey'] = sesskey();

        $data = $this->get_web_service_data();
        $data['invalid'] = 'test';

        $response = external_api::call_external_function('tool_userupsert_upsert_users', [
            'users' => [$data]
        ]);

        $this->verify_error($response);
        $this->assertSame(
            'Invalid parameter value detected',
            $response['exception']->message
        );
        $this->assertStringContainsString(
            'users => Invalid parameter value detected (Unexpected keys (invalid) detected in parameter array.): Unexpected keys (invalid) detected in parameter array',
            $response['exception']->debuginfo
        );
    }

    /**
     * Test upsert if the plugin is not configured
     */
    public function test_upsert_the_plugin_is_not_configured() {
        $this->resetAfterTest();
        $this->set_test_config_data();

        $this->config = new config();
        $this->setAdminUser();

        // Workaround to be able to call external_api::call_external_function.
        $_POST['sesskey'] = sesskey();

        $data = $this->get_web_service_data();

        // Break config.
        set_config('data_map_username', '', 'tool_userupsert');

        $response = external_api::call_external_function('tool_userupsert_upsert_users', [
            'users' => [$data]
        ]);

        $this->verify_error($response);
        $this->assertSame(
            'Upsert plugin is not configured',
            $response['exception']->message
        );
    }

    /**
     * Test upsert if it fails for a user.
     */
    public function test_upsert_failed() {
        $this->resetAfterTest();
        $this->set_test_config_data();

        $this->config = new config();
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();

        // Workaround to be able to call external_api::call_external_function.
        $_POST['sesskey'] = sesskey();

        // Emulate upsert error because of taken email.
        $data = $this->get_web_service_data();
        $data[$this->config->get_data_mapping()['email']] = $user->email;

        $sink = $this->redirectEvents();

        $response = external_api::call_external_function('tool_userupsert_upsert_users', [
            'users' => [$data, $data]
        ]);

        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(2, $events);

        $this->verify_success($response);
        $this->assertCount(2, $response['data']);

        foreach ($response['data'] as $warning) {
            $this->assertIsArray($warning);
            $this->assertArrayHasKey('itemid', $warning);
            $this->assertArrayHasKey('error', $warning);

            $this->assertSame('test', $warning['itemid']);
            $this->assertSame('Email is already taken: ' . $user->email, $warning['error']);
        }
    }
    /**
     * Test upsert if matching field is missing.
     */
    public function test_upsert_failed_because_missing_matching_field() {
        $this->resetAfterTest();
        $this->set_test_config_data();

        $this->config = new config();
        $this->setAdminUser();

        // Workaround to be able to call external_api::call_external_function.
        $_POST['sesskey'] = sesskey();

        // Emulate upsert error because of taken email.
        $data = $this->get_web_service_data();
        $data[$this->config->get_data_mapping()['username']] = '';

        $sink = $this->redirectEvents();

        $response = external_api::call_external_function('tool_userupsert_upsert_users', [
            'users' => [$data]
        ]);

        $this->verify_success($response);

        $this->assertCount(1, $response['data']);
        $this->assertArrayHasKey('itemid', $response['data'][0]);
        $this->assertArrayHasKey('error', $response['data'][0]);

        $this->assertSame('not set', $response['data'][0]['itemid']);
        $this->assertSame(
            'Missing mandatory field ' . $this->config->get_data_mapping()['username'],
            $response['data'][0]['error']
        );

        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertArrayHasKey('error', $event->get_data()['other']);
        $this->assertArrayHasKey('itemid', $event->get_data()['other']);

        $this->assertSame(
            "Failed upserting user: 'not set', error 'Missing mandatory field {$this->config->get_data_mapping()['username']}'",
            $event->get_description()
        );
        $this->assertSame('not set', $event->get_data()['other']['itemid']);
        $this->assertSame(
            'Missing mandatory field ' . $this->config->get_data_mapping()['username'],
            $event->get_data()['other']['error']
        );
    }

    /**
     * Test successful upsert.
     */
    public function test_upsert_succeed() {
        $this->resetAfterTest();
        $this->set_test_config_data();
        $this->config = new config();

        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/userupsert:upsert', CAP_ALLOW, $roleid, context_system::instance());

        $usertodelete = $this->getDataGenerator()->create_user(['auth' => 'manual']);
        $user = $this->getDataGenerator()->create_user(['auth' => 'manual']);
        $this->getDataGenerator()->role_assign($roleid, $user->id, context_system::instance()->id);

        $this->setUser($user);

        // Workaround to be able to call external_api::call_external_function.
        $_POST['sesskey'] = sesskey();

        $feed = ['users' => []];

        $data = $this->get_web_service_data();

        // User 1.
        $data[$this->config->get_data_mapping()['username']] = 'user1';
        $data[$this->config->get_data_mapping()['email']] = 'user1@email.ru';
        $feed['users'][] = $data;
        $this->assertFalse(get_complete_user_data('username', 'user1'));

        // User 2.
        $data[$this->config->get_data_mapping()['username']] = 'user2';
        $data[$this->config->get_data_mapping()['email']] = 'user2@email.ru';
        $data[$this->config->get_data_mapping()['auth']] = 'nologin';
        $feed['users'][] = $data;
        $this->assertFalse(get_complete_user_data('username', 'user2'));

        // User 3.
        $data[$this->config->get_data_mapping()['username']] = 'user3';
        $data[$this->config->get_data_mapping()['email']] = $user->email;
        $data[$this->config->get_data_mapping()['auth']] = 'manual';
        $feed['users'][] = $data;
        $this->assertFalse(get_complete_user_data('username', 'user3'));

        // Existing user 1.
        $data[$this->config->get_data_mapping()['username']] = $user->username;
        $data[$this->config->get_data_mapping()['email']] = 'user4@email.ru';
        $data[$this->config->get_data_mapping()['auth']] = 'email';
        $data[$this->config->get_data_mapping()['status']] = 'suspended';
        $feed['users'][] = $data;

        $existing = get_complete_user_data('username', $user->username);
        $this->assertSame('manual', $existing->auth);
        $this->assertSame($user->email, $existing->email);

        // Existing user 2.
        $data[$this->config->get_data_mapping()['username']] = $usertodelete->username;
        $data[$this->config->get_data_mapping()['email']] = 'user5@email.ru';
        $data[$this->config->get_data_mapping()['status']] = 'deleted';
        $feed['users'][] = $data;

        $sink = $this->redirectEvents();

        $response = external_api::call_external_function('tool_userupsert_upsert_users', $feed);
        $this->verify_success($response);
        $this->assertCount(1, $response['data']);

        $events = $sink->get_events();
        $sink->close();

        $succeededevents = array_filter($events, function ($event) {
            return $event instanceof upsert_succeeded;
        });

        $failedevents = array_filter($events, function ($event) {
            return $event instanceof upsert_failed;
        });

        $this->assertCount(4, $succeededevents);
        $this->assertCount(1, $failedevents);

        $succeeded = [];
        foreach ($succeededevents as $succeededevent) {
            $this->assertArrayHasKey('itemid', $succeededevent->get_data()['other']);
            $succeeded[] = $succeededevent->get_data()['other']['itemid'];
        }

        $failed = [];
        foreach ($failedevents as $failedevent) {
            $this->assertArrayHasKey('error', $failedevent->get_data()['other']);
            $this->assertArrayHasKey('itemid', $failedevent->get_data()['other']);
            $failed[] = $failedevent->get_data()['other']['itemid'];
        }

        $this->assertArrayHasKey('itemid', $response['data'][0]);
        $this->assertArrayHasKey('error', $response['data'][0]);
        $this->assertSame('user3', $response['data'][0]['itemid']);
        $this->assertSame('Email is already taken: ' . $user->email, $response['data'][0]['error']);

        $user1 = get_complete_user_data('username', 'user1');
        $user2 = get_complete_user_data('username', 'user2');
        $user3 = get_complete_user_data('username', 'user3');
        $userexisting = get_complete_user_data('username', $user->username);
        $userdeleted = get_complete_user_data('username', $usertodelete->username);

        $this->assertTrue(in_array($user1->username, $succeeded));
        $this->assertSame('Test', $user1->firstname);
        $this->assertSame('Test', $user1->lastname);
        $this->assertSame('user1@email.ru', $user1->email);
        $this->assertSame('manual', $user1->auth);
        $this->assertEquals(0, $user1->suspended);

        $this->assertTrue(in_array($user2->username, $succeeded));
        $this->assertSame('Test', $user2->firstname);
        $this->assertSame('Test', $user2->lastname);
        $this->assertSame('user2@email.ru', $user2->email);
        $this->assertSame('nologin', $user2->auth);
        $this->assertEquals(0, $user2->suspended);

        $this->assertTrue(in_array('user3', $failed));
        $this->assertFalse($user3);

        $this->assertTrue(in_array($userexisting->username, $succeeded));
        $this->assertSame('Test', $userexisting->firstname);
        $this->assertSame('Test', $userexisting->lastname);
        $this->assertSame('user4@email.ru', $userexisting->email);
        $this->assertSame('email', $userexisting->auth);
        $this->assertEquals(1, $userexisting->suspended);

        $this->assertTrue(in_array($usertodelete->username, $succeeded));
        $this->assertFalse($userdeleted);
    }

}
