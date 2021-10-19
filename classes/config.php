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
 * Config class.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userupsert;

use admin_settingpage;
use admin_setting_heading;
use admin_setting_configselect;
use lang_string;
use core_text;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Config class.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config {

    /**
     * A list of base mandatory fields.
     */
    const BASE_MANDATORY_FIELDS = ['username', 'lastname', 'firstname', 'email', 'status'];

    /**
     * A list of configured web service field and their descriptions.
     * @var array
     */
    private $webservicefields = [];

    /**
     * A field to find a user in Moodle by.
     * @var string
     */
    private $usermatchfield = 'username';

    /**
     * Fields map config data.
     * @var array
     */
    private $datamapping = [];

    /**
     * Default auth method.
     * @var array
     */
    private $defaultauth = 'manual';

    /**
     * Constructor.
     */
    public function __construct() {
        $config = get_config('tool_userupsert');

        if (!empty($config->webservicefields)) {
            $fields = explode("\n", str_replace("\r\n", "\n", $config->webservicefields));

            foreach ($fields as $fieldstring) {
                $field = new \stdClass();
                $parts = explode('|', $fieldstring);
                if (count($parts) === 2) {
                    $field->name = trim($parts[0]);
                    $field->description = trim($parts[1]);

                    if ($this->is_valid_webservice_field($field)) {
                        $this->webservicefields[$field->name] = $field->description;
                    }
                }
            }
        }

        if (!empty($config->usermatchfield)) {
            $this->usermatchfield = $config->usermatchfield;
        }

        $fieldmap = array_filter((array)$config, function($value, $key) {
            return (preg_match("/^data_map_(.+)$/", $key) && !empty($value));
        }, ARRAY_FILTER_USE_BOTH);

        foreach ($fieldmap as $name => $value) {
            $name = preg_replace("/data_map_/", "", $name, 1);
            $this->datamapping[$name] = $value;
        }

        if (!empty($config->defaultauth)) {
            $this->defaultauth = $config->defaultauth;
        }
    }

    /**
     * Return list of web service fields.
     *
     * @return array
     */
    public function get_web_service_fields(): array {
        return $this->webservicefields;
    }

    /**
     * Gets a list of mandatory fields.
     *
     * @return string[]
     */
    public function get_mandatory_fields(): array {
        $fields = self::BASE_MANDATORY_FIELDS;

        if (!in_array($this->usermatchfield, $fields)) {
            $fields[] = $this->usermatchfield;
        }

        return $fields;
    }

    /**
     * Gets user match field.
     *
     * @return string
     */
    public function get_user_match_field(): string {
        return $this->usermatchfield;
    }

    /**
     * Gets fields mapping config data.
     *
     * @return array
     */
    public function get_data_mapping(): array {
        return $this->datamapping;
    }

    /**
     * Check if config is ready to be used.
     *
     * @return bool
     */
    public function is_ready(): bool {
        // WS fields are not configured.
        if (empty($this->webservicefields)) {
            return false;
        }

        // Matching field must be matched.
        if (!key_exists($this->usermatchfield, $this->datamapping)) {
            return false;
        }

        // All mandatory fields must be matched.
        foreach ($this->get_mandatory_fields() as $name) {
            if (!key_exists($name, $this->datamapping)) {
                return false;
            }
        }

        // Mapping must be done using configured web service fields.
        foreach ($this->datamapping as $name => $value) {
            if (!key_exists($value, $this->webservicefields)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns configured auth method.
     */
    public function get_default_auth(): string {
        return $this->defaultauth;
    }

    /**
     * Adds data mapping to the settings page.
     *
     * @param \admin_settingpage $settings Settings.
     */
    public function display_data_mapping_settings(admin_settingpage $settings) {
        global $OUTPUT;

        $settings->add(new admin_setting_heading(
            'tool_userupsert/data_mapping',
            new lang_string('auth_data_mapping', 'auth'),
            get_string('datamapping', 'tool_userupsert')
        ));

        foreach (profile_fields::get_profile_fields() as $field => $description) {
            $fieldname = $field;

            if (profile_fields::is_custom_profile_field($field)) {
                $fieldname = profile_fields::get_field_short_name($field);
            }

            // This is pretty much copy of lib/authlib.php:display_auth_lock_options.
            if (core_text::strlen($fieldname) > 67) {
                // Display a message that the field can not be mapped because it's too long.
                $url = new moodle_url('/user/profile/index.php');
                $a = (object)['fieldname' => s($fieldname), 'shortname' => s($field), 'charlimit' => 67, 'link' => $url->out()];
                $settings->add(new admin_setting_heading('tool_userupsert/field_not_mapped_'.sha1($field), '',
                    get_string('cannotmapfield', 'auth', $a)));
            } else {
                $error = '';

                if ($this->is_missing_mapping($field)) {
                    $error = $OUTPUT->notification(get_string('mappingerror', 'tool_userupsert'));
                }

                $choices = ['' => get_string('none')];
                foreach ($this->webservicefields as $wsfield => $notused) {
                    $choices[$wsfield] = $wsfield;
                }

                // We are mapping to a remote field here.
                $settings->add(new admin_setting_configselect("tool_userupsert/data_map_{$field}",
                    get_string('auth_fieldmapping', 'auth', $description), $error, '', $choices));
            }
        }
    }

    /**
     * Check if the provided field missing required mapping.
     *
     * @param string $field Name of the field.
     * @return bool
     */
    private function is_missing_mapping(string $field): bool {
        // All mandatory fields require correct mapping.
        if (in_array($field, $this->get_mandatory_fields())) {
            // Mapping is missing or mapped to the value that is not actually a webservice field.
            if (!key_exists($field, $this->datamapping) || !key_exists($this->datamapping[$field], $this->webservicefields)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the given field is valid.
     *
     * @param \stdClass $field Field to check.
     * @return bool
     */
    private function is_valid_webservice_field(\stdClass $field): bool {
        if (!property_exists($field, 'name') || !property_exists($field, 'description')) {
            return false;
        }

        return !empty($field->name) && !strpos($field->name, ' ') && !empty($field->description);
    }

}
