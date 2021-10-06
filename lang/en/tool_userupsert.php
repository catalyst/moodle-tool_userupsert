<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     tool_userupsert
 * @category    string
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'User upsert';
$string['privacy:metadata'] = 'The User upsert plugin does not store any personal data.';
$string['fields'] = 'Web service fields';
$string['fields_desc'] = 'A list of expected user fields for the web service and their descriptions. The field name followed by a pipe (|) and the description of that field. Only one field per line, separated by a line break in between each field.
Those fields will appear in the <a href="/admin/webservice/documentation.php">Moodle API Documentation documentation</a> for tool_userupsert_upsert_users web service.

For example:
<pre>
userName | Username field.
authType | Auth type of the user.
</pre>';
