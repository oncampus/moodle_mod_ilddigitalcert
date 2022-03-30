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
 * mod_ilddigitalcert data generator
 *
 * @package     mod_ilddigitalcert
 * @category    test
 * @copyright   2022 Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Ilddigitralcert module data generator class
 *
 * @package     mod_ilddigitalcert
 * @category    test
 * @copyright   2022 Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ilddigitalcert_generator extends testing_module_generator {

    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/resourcelib.php');

        $record = (object)(array)$record;

        if (!isset($record->name)) {
            $record->name = 'Test certificate';
        }
        if (!isset($record->description)) {
            $record->description = 'This certificate certifies the successful course participation.';
        }
        if (!isset($record->issuer)) {
            $record->issuer = 1;
        }
        if (!isset($record->expertise)) {
            $record->expertise = 'Ability to participate in a moodle course
                Basic knowledge of moodle features';
        }
        if (!isset($record->examination_place)) {
            $record->examination_place = 'Online';
        }

        return parent::create_instance($record, (array)$options);
    }
}
