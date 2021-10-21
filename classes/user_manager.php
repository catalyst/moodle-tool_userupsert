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
 * The class responsible for managing user data.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userupsert;

defined('MOODLE_INTERNAL') || die();

/**
 * The class responsible for managing user data.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_manager {

    /**
     * A list of valid user statuses.
     */
    const VALID_USER_STATUSES = ['active', 'suspended', 'deleted'];

    /**
     * Config instance.
     * @var \tool_userupsert\config
     */
    private $config;

    /**
     * WS matching field.
     * @var string
     */
    private $matchingfield;

    /**
     * WS username field.
     * @var string
     */
    private $usernamefield;

    /**
     * WS email field.
     * @var string
     */
    private $emailfield;

    /**
     * WS user status field.
     * @var string
     */
    private $statusfield;

    /**
     * WS auth field.
     * @var string
     */
    private $authfield;

    /**
     * WS password field.
     * @var string
     */
    private $passwordfield;

    /**
     * Constructor.
     *
     * @param \tool_userupsert\config $config
     */
    public function __construct(config $config) {
        $this->config = $config;

        if (!$this->config->is_ready()) {
            throw new upsert_not_configured_exception();
        }

        if (empty($this->config->get_data_mapping()[$this->config->get_user_match_field()])) {
            throw new upsert_not_configured_exception();
        }

        if (empty($this->config->get_data_mapping()['username'])) {
            throw new upsert_not_configured_exception();
        }

        if (empty($this->config->get_data_mapping()['email'])) {
            throw new upsert_not_configured_exception();
        }

        if (empty($this->config->get_data_mapping()['status'])) {
            throw new upsert_not_configured_exception();
        }

        $this->matchingfield = $this->config->get_data_mapping()[$this->config->get_user_match_field()];
        $this->usernamefield = $this->config->get_data_mapping()['username'];
        $this->emailfield = $this->config->get_data_mapping()['email'];
        $this->statusfield = $this->config->get_data_mapping()['status'];

        if (!empty($this->config->get_data_mapping()['auth'])) {
            $this->authfield = $this->config->get_data_mapping()['auth'];
        }
        if (!empty($this->config->get_data_mapping()['password'])) {
            $this->passwordfield = $this->config->get_data_mapping()['password'];
        }
    }

    /**
     * Upserts user based on provided data.
     *
     * @param array $data User data where the key is field and value is value.
     */
    public function upsert_user(array $data) {
        global $CFG;

        // Check all mandatory fields are in data.
        foreach ($this->config->get_mandatory_fields() as $field) {
            $fieldname = $this->config->get_data_mapping()[$field];
            if (empty($data[$fieldname])) {
                throw new upset_failed_exception('error:missingfield', $this->config->get_data_mapping()[$field]);
            }
        }

        $status = $data[$this->statusfield];
        $this->validate_status($status);

        $user = user_extractor::get_user($this->config->get_user_match_field(), $data[$this->matchingfield]);

        if ($status == 'deleted') {
            if (!empty($user)) {
                $this->delete_user($user);
            }
        } else {
            $userid = null;
            $updatepasword = false;

            if ($user) {
                $userid = $user->id;
                if (isset($data[$this->passwordfield])) {
                    $updatepasword = true;
                }
            }

            $email = $data[$this->emailfield];
            $this->validate_email($email);

            if (empty($CFG->allowaccountssameemail) && user_extractor::is_email_taken($email, $userid)) {
                throw new upset_failed_exception('error:emailtaken', $email);
            }

            $username = $data[$this->usernamefield];
            if (user_extractor::is_username_taken($username, $userid)) {
                throw new upset_failed_exception('error:usernametaken', $username);
            }

            $auth = $this->config->get_default_auth();
            if (!empty($this->authfield) && !empty($data[$this->authfield])) {
                $auth = $data[$this->authfield];
            }

            if (!exists_auth_plugin($auth)) {
                throw new upset_failed_exception('error:invalidauth', $auth);
            }

            try {
                if (!$user) {
                    // Initially create a user with an empty password.
                    $password = '';
                    $user = create_user_record($username, $password, $auth);

                    // However, if the password field exists in the data, then make sure we update it with all other fields.
                    // This will help us to validate the password as create_user_record doesn't validate it.
                    if (!empty($this->passwordfield) && isset($data[$this->passwordfield])) {
                        $user->password = $data[$this->passwordfield];
                        $updatepasword = true;
                    }
                }
            } catch (\Exception $exception) {
                throw new upset_failed_exception('error:creating', null, $exception->getMessage());
            }

            $user->suspended = 0;
            if ($status == 'suspended') {
                $user->suspended = 1;
            }

            try {
                $this->update_user_profile($user, $data, $updatepasword);
            } catch (\Exception $exception) {
                throw new upset_failed_exception('error:updatingfields', null, $exception->getMessage());
            }
        }
    }

    /**
     * Validate user status.
     *
     * @param string $status Status to validate.
     */
    private function validate_status(string $status) {
        if (!in_array($status, self::VALID_USER_STATUSES)) {
            throw new upset_failed_exception('error:invalidstatus', $status);
        }

    }

    /**
     * Validate email.
     *
     * @param string $email Email to validate.
     */
    private function validate_email(string $email) {
        if (!validate_email($email)) {
            throw new upset_failed_exception('error:invalidemail', $email);
        }

        if ($error = email_is_not_allowed($email)) {
            throw new upset_failed_exception('error:notallowedemail', $email, $error);
        }

    }

    /**
     * Delete provided user.
     *
     * @param \stdClass $user User object.
     */
    private function delete_user(\stdClass $user) {
        try {
            delete_user($user);
        } catch (\Exception $exception) {
            throw new upset_failed_exception('error:deleting', null, $exception->getMessage());
        }
    }

    /**
     * Update user profile data including password.
     *
     * @param \stdClass $user User object.
     * @param array $data Web service data.
     * @param bool $updatepasword Should update the password?
     */
    private function update_user_profile(\stdClass &$user, array $data, bool $updatepasword) {
        global $CFG;

        foreach ($this->config->get_data_mapping() as $moodlefield => $webservicefield) {
            if (isset($data[$webservicefield])) {
                $user->$moodlefield = $data[$webservicefield];
            }
        }

        require_once($CFG->dirroot.'/user/lib.php');
        if ($user->description === true) {
            // Function get_complete_user_data() sets description = true to avoid keeping in memory.
            // If set to true - don't update based on data from this call.
            unset($user->description);
        }

        $errors = profile_validation($user, []);
        if (!empty($errors)) {
            // Format error array.
            $debugginginfo  = implode(', ', array_map(
                function ($v, $k) {
                    return sprintf("%s: %s", $k, $v);
                }, $errors, array_keys($errors)
            ));
            throw new upset_failed_exception('error:customfield', null, $debugginginfo);
        }

        profile_save_data($user);
        user_update_user($user, $updatepasword);
    }

}
