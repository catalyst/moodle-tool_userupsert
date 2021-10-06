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
     * Test get fields when empty config.
     */
    public function test_get_fields_empty_config() {
        $config = new config();
        $this->assertEmpty($config->get_fields());
    }

    /**
     * Test get fields.
     */
    public function test_get_fields() {
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
        set_config('fields', $testsetting, 'tool_userupsert');

        $config = new config();
        $expected = [
            'field1' => 'Description 1',
            'field2' => 'Description 2',
            'field3' => 'Description 3',
        ];

        $this->assertSame($expected, $config->get_fields());
    }

}
