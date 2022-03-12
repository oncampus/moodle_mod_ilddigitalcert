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
 * Form to reissue certificates.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2022, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ilddigitalcert\output\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/formslib.php");

/**
 * Form to reissue certificates.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2021, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reissue_form extends \moodleform {
    /**
     * Form definition.
     * @return void
     */
    public function definition() {

        $mform = $this->_form;

        // Private key input.
        $mform->addElement('hidden', 'selected', '', array('id' => 'm-element-reissue__selected'));
        $mform->setType('selected', PARAM_NOTAGS);

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submit', get_string('reissue', 'ilddigitalcert'), array('id' => 'm-element-reissue__submit'));
        $buttonarray[] = $mform->createElement('button', 'cancel', get_string('cancel'), array('id' => 'm-element-reissue__cancel'));
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


    /**
     * Reissues the certificates that were defined in the form.
     *
     * @return array
     **/
    public function action() {
        global $DB;

        // Get form data.
        $data = $this->get_data();
        $selected_certs = json_decode($data->selected);

        if (empty($selected_certs)) return null;
        // Get certificate records from selected ids.
        list($insql, $inparams) = $DB->get_in_or_equal($selected_certs);
        $sql = "SELECT * FROM {ilddigitalcert_issued} WHERE txhash IS NULL AND id $insql";
        $certificates = $DB->get_records_sql($sql, $inparams);

        // Write selected certificates to blockchain
        foreach ($certificates as $certificate) {
            if (!$reissueuser = $DB->get_record('user', array('id' => $certificate->userid, 'confirmed' => 1, 'deleted' => 0))) return null;

            list($course, $cm) = get_course_and_cm_from_cmid($certificate->cmid, 'ilddigitalcert');
            $certmetadata = generate_certmetadata($cm, $reissueuser);
            reissue_certificate($certmetadata, $certificate->userid, $cm->id);

            $recipient = $certmetadata->{'extensions:recipientB4E'};
            $recipientname = $recipient->givenname . ' ' . $recipient->surname;

            \core\notification::success(get_string('reissue_success', 'mod_ilddigitalcert', $recipientname));
        }

        $invalid_count = count($selected_certs) - count($certificates);
        if ($invalid_count > 0) {
            \core\notification::warning(get_string('reissue_error_already_signed', 'mod_ilddigitalcert', $invalid_count));
        }
    }
}
