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
 * Missing field exception.
 *
 * @package     tool_userupsert
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userupsert;

defined('MOODLE_INTERNAL') || die();

/**
 * Missing field exception.
 *
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upsert_failed_exception extends \moodle_exception {

    /**
     * Constructor
     *
     * @param string $errorcode Error code.
     * @param \stdClass|null $a Extra words and phrases that might be required in the error string.
     * @param string|null $debuginfo Additional debug information.
     */
    public function __construct(string $errorcode, ?\stdClass $a=null, ?string $debuginfo = null) {
        parent::__construct($errorcode, 'tool_userupsert', '', $a, $debuginfo);
    }

}
