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
 * The class responsible for user profile fields.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userupsert;

use core_user;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * The class responsible for user profile fields.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile_fields {

    /**
     * A list of user matching fields from {user} table
     */
    const MATCH_FIELDS_FROM_USER_TABLE = [
        'username',
        'idnumber',
        'email',
    ];

    /**
     * A list of supported types of profile fields.
     */
    const SUPPORTED_TYPES_OF_PROFILE_FIELDS = [
        'text'
    ];

    /**
     * Prefix for profile fields in the config.
     */
    const PROFILE_FIELD_PREFIX = 'profile_field_';

    /**
     * Build setting value for a  user profile field.
     *
     * @param string $shortname Short name of the profile field.
     * @return string
     */
    public static function prefix_custom_profile_field(string $shortname) : string {
        return self::PROFILE_FIELD_PREFIX . $shortname;
    }

    /**
     * Get a list of fields to be able to match by.
     *
     * @return string[]
     */
    public static function get_supported_match_fields() : array {
        $fields = [];

        foreach (self::MATCH_FIELDS_FROM_USER_TABLE as $name) {
            $fields[$name] = get_string($name);
        }

        $customfields = profile_get_custom_fields(true);

        if (!empty($customfields)) {
            $result = array_filter($customfields, function($customfield) {
                return in_array($customfield->datatype, self::SUPPORTED_TYPES_OF_PROFILE_FIELDS) &&
                    $customfield->forceunique == 1;
            });

            $customfieldoptions = array_column($result, 'name', 'shortname');

            foreach ($customfieldoptions as $key => $value) {
                $customfieldoptions[self::prefix_custom_profile_field($key)] = $value;
                unset($customfieldoptions[$key]);
            }

            $fields = array_merge($fields, $customfieldoptions);
        }

        return $fields;
    }

    /**
     * Check if provided field name is considered as profile field.
     *
     * @param string $fieldname User field name.
     * @return bool
     */
    public static function is_custom_profile_field(string $fieldname) : bool {
        return strpos($fieldname, self::PROFILE_FIELD_PREFIX) === 0;
    }

    /**
     * Get shortname from the profile field.
     *
     * @param string $fieldname Profile field name from config.
     * @return string
     */
    public static function get_field_short_name(string $fieldname) : string {
        if (self::is_custom_profile_field($fieldname)) {
            $fieldname = substr($fieldname, strlen(self::PROFILE_FIELD_PREFIX), strlen($fieldname));
        }

        return $fieldname;
    }

    /**
     * Returns a list of all profile fields where key is field and value is its description.
     * Custom profile fields are prefixed as self::PROFILE_FIELD_PREFIX.
     *
     * @return array
     */
    public static function get_profile_fields(): array {
        global $DB;

        $fields = [];

        $userfields = core_user::AUTHSYNCFIELDS;

        // Add extra required fields.
        array_unshift($userfields, "username");
        $userfields[] = 'auth';
        $userfields[] = 'password';
        $userfields[] = 'status';

        if (($key = array_search('url', $userfields)) !== false) {
            unset($userfields[$key]);
        }

        // Generate the list of profile fields to allow updates / lock.
        $customfields = array_column(profile_get_custom_fields(true), 'shortname', 'shortname');
        if (!empty($customfields)) {

            // Prefix custom profile fields to be able to distinguish.
            array_walk($customfields, function(&$value) {
                $value = self::PROFILE_FIELD_PREFIX . $value;
            });

            $userfields = array_merge($userfields, $customfields);
            $customfieldname = $DB->get_records('user_info_field', null, '', 'shortname, name');
        }

        foreach ($userfields as $field) {
            // Define the fieldname we display to the  user.
            // this includes special handling for some profile fields.
            $fieldname = $field;

            if (!empty($customfields) && in_array($field, $customfields)) {
                $fielddescription = $customfieldname[self::get_field_short_name($field)]->name;
            } else {
                switch ($field) {
                    case 'lang':
                        $fielddescription = get_string('language');
                        break;
                    case 'auth':
                        $fielddescription = get_string('auth', 'tool_userupsert');
                        break;
                    case 'status':
                        $fielddescription = get_string('status', 'tool_userupsert');
                        break;
                    default:
                        $fielddescription = get_string($fieldname);
                }
            }

            $fields[$field] = $fielddescription;
        }

        return $fields;
    }

}
