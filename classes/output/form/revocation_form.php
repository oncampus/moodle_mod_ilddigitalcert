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

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Form to revoke a selected certificate in the blockchain.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2022, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class revocation_form extends \moodleform {
    /**
     * Form definition.
     * @return void
     */
    public function definition() {

        $mform = $this->_form;

        // Private key input.
        $pkattributes = array(
            'id' => 'm-element-revoke__pk',
            'class' => 'pk-input'
        );
        $mform->addElement('password', 'pk', 'Private Key', $pkattributes);
        $mform->addRule('pk', get_string('invalid_pk_format', 'ilddigitalcert'), 'regex', '/[A-Za-z0-9]{64}/', 'client');

        $mform->addElement('hidden', 'selected', '', array('id' => 'm-element-revoke__selected'));
        $mform->setType('selected', PARAM_NOTAGS);

        $buttonarray = array();
        $buttonarray[] = $mform->createElement(
            'submit',
            'submit',
            get_string('revoke', 'ilddigitalcert'), array('id' => 'm-element-revoke__submit')
        );
        $buttonarray[] = $mform->createElement(
            'button',
            'cancel',
            get_string('cancel'), array('id' => 'm-element-revoke__cancel')
        );
        $mform->addGroup($buttonarray, 'actionbuttons', '', array(' '), false);
    }

    /**
     * Gets input data of submitted form.
     *
     * @return \stdClass
     **/
    public function get_data() {
        $data = parent::get_data();

        if (empty($data)) {
            return false;
        }

        return $data;
    }


    /**
     * Registers the certificates in the blockchain that were defined in the form.
     *
     * @return array
     **/
    public function action() {
        global $DB, $USER;

        // Get form data.
        $data = $this->get_data();
        $selectedcerts = json_decode($data->selected);

        if (empty($selectedcerts)) {
            return null;
        }

        // Get certificate records from selected ids.
        list($insql, $inparams) = $DB->get_in_or_equal($selectedcerts);
        $sql = "SELECT * FROM {ilddigitalcert_issued} WHERE certhash IS NOT NULL AND id $insql";
        $certificates = $DB->get_records_sql($sql, $inparams);

        // Write selected certs to the blockchain with the given private key.
        foreach ($certificates as $issuedcertificate) {
            if (\mod_ilddigitalcert\manager::revoke($issuedcertificate, $USER, $data->pk)) {
                \core\notification::success(get_string('revoked', 'mod_ilddigitalcert'));
            }
        }

        $invalidcount = count($selectedcerts) - count($certificates);
        if ($invalidcount > 0) {
            \core\notification::warning(get_string('revoke_error_invalid', 'mod_ilddigitalcert', $invalidcount));
        }
    }
}
