![Build Status](https://github.com/catalyst/moodle-tool_userupsert/actions/workflows/ci.yml/badge.svg?branch=MOODLE_39_STABLE)

# Upsert users #

An admin tool provides a web service for inserting/updating/deleting users using just one API endpoint.

## Why does this plugin exist? ##

Moodle core provides a comprehensive list of webservices to create, update or delete users (core_user_create_users, 
core_user_update_users and core_user_delete_users). However, in those services you must use an internal user ID 
to identify users. In some systems with configured SSO integration, an identity provider doesn't know about internal 
user ID and find users using different fields (e.g. username, idnumber or unique custom profile field). In this case,
it's not possible to use core webservices. 

## Usage ##

This plugin provides a single API endpoint (tool_userupsert_upsert_users) for managing users. You can select a field 
that will be used for matching users, including unique custom profile fields. Also, you can customise a list of required
fields for your web service. 

You need to configure your Moodle for using Web services. See documentation https://docs.moodle.org/311/en/Web_services

After installing the plugin, navigate to Site administration > Plugins > Admin tools > User upsert and configure the 
plugin.

Then, navigate to Site administration > Plugins > Web services > API Documentation and check WS documentation for 
tool_userupsert_upsert_users service as it will depend on your configuration.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/admin/tool/userupsert

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2021 Catalyst IT

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
