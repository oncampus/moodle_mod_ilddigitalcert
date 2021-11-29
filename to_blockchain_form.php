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
 * Form that lets sign and register certificates in the blockchain.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2021, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . "/formslib.php");

/**
 * Form that lets sign and register certificates in the blockchain.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2021, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ilddigialcert_to_blockchain_form extends moodleform {
    /**
     * Form definition.
     * @return void
     */
    public function definition() {

        $mform = $this->_form;

        // Private key input.
        $pk_attributes = array(
            'id' => 'm-element-to-bc__pk',
            'class' => 'pk-input'
        );
        $mform->addElement('password', 'pk', 'Private Key', $pk_attributes);
        $mform->addRule('pk', get_string('invalid_pk_format', 'ilddigitalcert'), 'regex', '/[A-Za-z0-9]{64}/', 'client');


        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'ueid');
        $mform->setType('ueid', PARAM_INT);
        $mform->addElement('hidden', 'selected', '', array('id' => 'm-element-to-bc__selected'));
        $mform->setType('selected', PARAM_NOTAGS);
        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_NOTAGS);

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submit', get_string('toblockchain', 'ilddigitalcert'), array('id' => 'm-element-to-bc__submit'));
        $buttonarray[] = $mform->createElement('button', 'cancel', get_string('cancel'), array('id' => 'm-element-to-bc__cancel'));
        $mform->addGroup($buttonarray, 'actionbuttons', '', array(' '), false);
    }

    /**
     * Gets input data of submitted form.
     *
     * @return object
     **/
    public function get_data() {
        $data = parent::get_data();

        if (empty($data)) {
            return false;
        }

        return $data;
    }
}
