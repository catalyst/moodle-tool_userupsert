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
$string['webservicefields'] = 'Web service fields';
$string['webservicefields_desc'] = 'A list of expected user fields for the web service and their descriptions. The field name followed by a pipe (|) and the description of that field. Only one field per line, separated by a line break in between each field.
Those fields will appear in the <a href="/admin/webservice/documentation.php">Moodle API Documentation documentation</a> for tool_userupsert_upsert_users web service.

For example:
<pre>
userName | Username field.
authType | Auth type of the user.
</pre>';
$string['userupsert:upsert'] = 'Upsert users';
$string['datamapping'] = 'Use the following options to map data from Web service fields to user profile fields. Any data not mapped to a profile field will be ignored.';
$string['usermatchfield'] = 'User match field';
$string['usermatchfield_desc'] = 'This field will be used for finding a user in Moodle. Make sure that you have mapped this it with Web service fields in Data mapping.';
$string['notconfigured'] = 'The plugin is not configured properly. Please check the errors below.';
$string['mappingerror'] = 'Web service field mapping is required for this field.';
$string['defaultauth'] = 'Default authentication method';
$string['auth'] = 'Authentication method';
$string['defaultauth_desc'] = 'If an authentication method is not provided in Web service data, then this auth method will be set by default.';
$string['status'] = 'Status - active, deleted or suspended';
$string['error:morethanoneuser'] = 'More than one user found.';
$string['error:missingfield'] = 'Missing mandatory field {$a}';
$string['error:notconfigured'] = 'Upsert plugin is not configured';
$string['error:customfield'] = 'Error setting custom field data';
$string['error:deleting'] = 'Error deleting a user';
$string['error:notallowedemail'] = 'Email is not allowed: {$a}';
$string['error:invalidemail'] = 'Invalid email: {$a}';
$string['error:invalidstatus'] = 'Invalid status: {$a}';
$string['error:updatingfields'] = 'Error updating profile fields';
$string['error:invalidauth'] = 'Invalid auth method: {$a}';
$string['error:usernametaken'] = 'Username is already taken: {$a}';
$string['error:emailtaken'] = 'Email is already taken: {$a}';
