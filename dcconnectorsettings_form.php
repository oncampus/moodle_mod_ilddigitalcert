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
 * Form for connector (Digital Campus) settings.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2021 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class dcconnectorsettings_form extends moodleform {
    // Add elements to form.
    public function definition() {
        $mform = $this->_form; // Don't forget the underscore!

        $mform->addElement('header', 'headerconfig', get_string('dcconnectorsettings', 'mod_ilddigitalcert'));

        $attributes = array('size' => '30',
            'maxlength' => '100');

        $mform->addElement('text', 'dchost', get_string('dchost', 'mod_ilddigitalcert'), $attributes);
        $mform->addRule('dchost', null, 'required', null, 'client');
        $mform->setType('dchost', PARAM_RAW);

        $mform->addElement('text', 'dcxapikey', get_string('dcxapikey', 'mod_ilddigitalcert'), $attributes);
        $mform->addRule('dcxapikey', null, 'required', null, 'client');
        $mform->setType('dcxapikey', PARAM_RAW);

        $mform->addElement('text', 'dcconnectorid', get_string('dcconnectorid', 'mod_ilddigitalcert'), $attributes);
        $mform->addRule('dcconnectorid', null, 'required', null, 'client');
        $mform->setType('dcconnectorid', PARAM_RAW);

        $this->add_action_buttons(true, get_string('save'));
    }

    // Custom validation should be added here.
    public function validation($data, $files) {
        return array();
    }
}