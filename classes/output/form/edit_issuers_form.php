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

namespace mod_ilddigitalcert\output\form;

/**
 * Form for adding, editing and removing certification authorities edit_issuers.php).
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_issuers_form extends \moodleform {
    /**
     * Form defintion
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        if (isset($this->_customdata['issuerid'])) {
            $mform->addElement('hidden', 'issuerid', $this->_customdata['issuerid']);
            $mform->setType('issuerid', PARAM_INT);
            $mform->setConstant('issuerid', $this->_customdata['issuerid']);
        }

        $mform->addElement('header', 'headerconfig', get_string('headerconfig', 'mod_ilddigitalcert'));

        $attributes = array('size' => '30',
            'maxlength' => '100');

        $mform->addElement('text', 'issuername_label', get_string('issuername_label', 'mod_ilddigitalcert'), $attributes);
        $mform->addRule('issuername_label', null, 'required', null, 'client');
        $mform->setType('issuername_label', PARAM_RAW);

        $mform->addElement('textarea', 'issuerdescription', get_string('issuerdescription', 'mod_ilddigitalcert'));
        $mform->addRule('issuerdescription', null, 'required', null, 'client');
        $mform->setType('issuerdescription', PARAM_RAW);

        // Image.
        $filemanageroptions = array();
        $filemanageroptions['accepted_types'] = 'image';
        $filemanageroptions['maxbytes'] = 0;
        $filemanageroptions['maxfiles'] = 1;
        $mform->addElement('filemanager', 'issuerimage', get_string("image", "mod_ilddigitalcert"), null, $filemanageroptions);

        $attributes = array('size' => '30',
            'maxlength' => '42');

        $mform->addElement('text', 'issueraddress', get_string('issueraddress', 'mod_ilddigitalcert'), $attributes);
        $mform->addRule('issueraddress', null, 'required', null, 'client');
        $mform->setType('issueraddress', PARAM_RAW);

        $attributes = array('size' => '30',
            'maxlength' => '100');

        $mform->addElement('text', 'issueremail', get_string('issueremail', 'mod_ilddigitalcert'), $attributes);
        $mform->addRule('issueremail', null, 'required', null, 'client');
        $mform->setType('issueremail', PARAM_RAW);

        $mform->addElement('text', 'issuerurl', get_string('issuerurl', 'mod_ilddigitalcert'), $attributes);
        $mform->addRule('issuerurl', null, 'required', null, 'client');
        $mform->setType('issuerurl', PARAM_RAW);

        $mform->addElement('text', 'issuerlocation', get_string('issuerlocation', 'mod_ilddigitalcert'), $attributes);
        $mform->addRule('issuerlocation', null, 'required', null, 'client');
        $mform->setType('issuerlocation', PARAM_RAW);

        $mform->addElement('text', 'issuerzip', get_string('issuerzip', 'mod_ilddigitalcert'));
        $mform->setType('issuerzip', PARAM_INT);

        $mform->addElement('text', 'issuerstreet', get_string('issuerstreet', 'mod_ilddigitalcert'), $attributes);
        $mform->setType('issuerstreet', PARAM_RAW);

        $mform->addElement('text', 'issuerpob', get_string('issuerpob', 'mod_ilddigitalcert'));
        $mform->setType('issuerpob', PARAM_INT);

        $this->add_action_buttons(true, get_string('save'));
    }

    /**
     * Load the issuer image as a draft before rendering the form.
     *
     * @param array $defaultvalues
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        if (isset($this->_customdata['issuerid']) and $this->_customdata['issuerid'] > 0) {
            if ($this->current->instance) {
                $draftitemid = file_get_submitted_draft_itemid('issuerimage');
                $context = \context_system::instance();
                file_prepare_draft_area($draftitemid,
                                        $context->id,
                                        'mod_ilddigitalcert',
                                        'issuer',
                                        $this->_customdata['issuerid'],
                                        array());
                $defaultvalues['issuerimage'] = $draftitemid;
            }
        }
    }
}
