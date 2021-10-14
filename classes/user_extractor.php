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

}
