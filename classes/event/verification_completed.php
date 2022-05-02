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
 * Verification completed event.
 *
 * @package    mod_ilddigitalcert
 * @copyright  2022 ISy TH Lübeck <dev.ild@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ilddigitalcert\event;

/**
 * Event triggered after a certificate was verified.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - boolean verification_result: True, or false wether the certificates authenticity was successfully verified.
 *      - string verification_method: type of data that was submitted to the verification process ('hash', 'pdf', 'xml', 'json').
 * }
 *
 * @package    mod_ilddigitalcert
 * @copyright  2022 ISy TH Lübeck <dev.ild@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class verification_completed extends \core\event\base {

    /**
     * Set basic properties for the event.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventverificationcompleted', 'mod_ilddigitalcert');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' tried to verify a certificate " .
            $this->other['verification_method'] .
            ". Result: " . ($this->other['verification_result'] ? "valid" : "invalid");
    }

    /**
     * Custom validations.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['verification_method'])) {
            throw new \coding_exception("The 'other['verification_method']' must be set.");
        }
        if (!isset($this->other['verification_result'])) {
            throw new \coding_exception("The 'other['verification_result']' must be set.");
        }
    }
}
