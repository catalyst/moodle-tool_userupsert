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
 * The class responsible for retrieving a user based on identifier.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userupsert;

use dml_missing_record_exception;
use dml_multiple_records_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * The class responsible for retrieving a user based on identifier.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_extractor {

    /**
     * Get extracted from DB user.
     *
     * @param string $fieldname Field name to search by.
     * @param string $fieldvalue Field value to search by.
     *
     * @return \stdClass|null
     */
    public static function get_user(string $fieldname, string $fieldvalue): ?\stdClass {
        global $DB, $CFG;

        $user = null;

        if (profile_fields::is_custom_profile_field($fieldname)) {
            $fieldname = profile_fields::get_field_short_name($fieldname);

            $joins = " LEFT JOIN {user_info_field} f ON f.shortname = :fieldname ";
            $joins .= " LEFT JOIN {user_info_data} d ON d.fieldid = f.id AND d.userid = u.id ";
            $fieldsql = " AND d.data = :fieldvalue";

            $params['fieldname'] = $fieldname;
            $params['fieldvalue'] = $fieldvalue;
            $params['mnethostid'] = $CFG->mnet_localhost_id;

            $sql = "SELECT u.id
                      FROM {user} u $joins
                     WHERE u.mnethostid = :mnethostid AND deleted <> 1 $fieldsql";

            if ($records = $DB->get_records_sql($sql, $params)) {
                if (count($records) !== 1) {
                    throw new more_than_one_user_found_exception();
                }

                $record = reset($records);
                $user = get_complete_user_data('id', $record->id);
            }
        } else {
            try {
                $user = get_complete_user_data($fieldname, $fieldvalue, null, true);
            } catch (dml_missing_record_exception $exception) {
                $user = null;
            } catch (dml_multiple_records_exception $exception) {
                throw new more_than_one_user_found_exception();
            }
        }

        if (empty($user)) {
            $user = null;
        }

        return $user;
    }

    /**
     * Check if given email is taken by other user(s).
     *
     * @param string $email Email to check.
     * @param int|null $excludeuserid A user id to exclude.
     *
     * @return bool
     */
    public static function is_email_taken(string $email, ?int $excludeuserid = null): bool {
        return self::is_user_field_taken('email', $email, $excludeuserid);
    }

    /**
     * Check if given email is taken by other user(s).
     *
     * @param string $username username to check.
     * @param int|null $excludeuserid A user id to exclude.
     *
     * @return bool
     */
    public static function is_username_taken(string $username, ?int $excludeuserid = null): bool {
        return self::is_user_field_taken('username', $username, $excludeuserid);
    }

    /**
     * Check if the field is taken.
     *
     * @param string $fieldname Field name.
     * @param string $value Field value.
     * @param int|null $excludeuserid A user id to exclude.
     *
     * @return bool
     */
    private static function is_user_field_taken(string $fieldname, string $value, ?int $excludeuserid = null): bool {
        global $CFG, $DB;

        if (!empty($fieldname)) {
            // Make a case-insensitive query for the given email address.
            $select = $DB->sql_equal($fieldname, ':field', false) . ' AND mnethostid = :mnethostid AND deleted = :deleted';
            $params = array(
                'field' => $value,
                'mnethostid' => $CFG->mnet_localhost_id,
                'deleted' => 0
            );

            if ($excludeuserid) {
                $select .= ' AND id <> :userid';
                $params['userid'] = $excludeuserid;
            }

            // If there are other user(s) that already have the same email, display an error.
            if ($DB->record_exists_select('user', $select, $params)) {
                return true;
            }
        }

        return false;
    }

}
