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
 * Plugin administration pages are defined here.
 *
 * @package     tool_userupsert
 * @category    admin
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_userupsert\config;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $config = new config();

    $settings = new admin_settingpage('tool_userupsert', get_string('pluginname', 'tool_userupsert'));
    $ADMIN->add('tools', $settings);

    if (!$config->is_ready()) {
        $error = $OUTPUT->notification(get_string('notconfigured', 'tool_userupsert'));
        $settings->add(new admin_setting_heading('tool_userupsert/generalsettings', '', $error));
    }

    $settings->add(new admin_setting_heading(
        'tool_userupsert/wsfields',
        get_string('webservicefields', 'tool_userupsert'),
        '')
    );

    $settings->add(new admin_setting_configtextarea(
        'tool_userupsert/webservicefields',
        get_string('webservicefields', 'tool_userupsert'),
        get_string('webservicefields_desc', 'tool_userupsert'),
        '')
    );

    $settings->add(new admin_setting_heading(
        'tool_userupsert/mapping',
        get_string('usermatchfield', 'tool_userupsert'),
        '')
    );

    $settings->add(new admin_setting_configselect(
        'tool_userupsert/usermatchfield',
        get_string('usermatchfield', 'tool_userupsert'),
        get_string('usermatchfield_desc', 'tool_userupsert'),
        'username',
        $config->get_supported_match_fields())
    );

    $config->display_data_mapping_settings($settings);

    $settings->add(new admin_setting_heading(
            'tool_userupsert/defaultauthheader',
            get_string('defaultauth', 'tool_userupsert'),
            '')
    );

    $authtypes = get_enabled_auth_plugins(true);
    $authselect = [];
    foreach ($authtypes as $type) {
        $auth = get_auth_plugin($type);
        $authselect[$type] = $auth->get_title();
    }

    $settings->add(new admin_setting_configselect(
        'tool_userupsert/defaultauth',
        get_string('defaultauth', 'tool_userupsert'),
        get_string('defaultauth_desc', 'tool_userupsert'),
        'manual',
        $authselect
    ));

}
