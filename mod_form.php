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
 * The main mod_ilddigitalcert configuration form.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once('locallib.php');

/**
 * Module instance settings form.
 *
 * @package    mod_ilddigitalcert
 * @copyright  2018 ILD TH Lübeck <support@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ilddigitalcert_mod_form extends moodleform_mod {
    /**
     * Used to prefix an encrypted private key.
     */
    const ENCRYPTED_PREFIX = 'encrypted';

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('ilddigitalcertname', 'mod_ilddigitalcert'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        $mform->addElement('header', 'configheader', get_string('certificate', 'mod_ilddigitalcert'));
        $mform->setExpanded('configheader');

        $issuers = array('a' => get_string('choose', 'mod_ilddigitalcert'));
        $records = $DB->get_records_sql('select id, name from {ilddigitalcert_issuer}', array());
        foreach ($records as $record) {
            $issuers[$record->id] = $record->name;
        }

        $mform->addElement('select', 'issuer', get_string('issuer', 'mod_ilddigitalcert'), $issuers);
        $mform->setDefault('issuer', 'a');
        $mform->addRule('issuer', get_string('error_choose', 'mod_ilddigitalcert'), 'numeric', null, 'client');
        $mform->addRule('issuer', null, 'required', null, 'client');

        $mform->addElement(
            'textarea',
            'description',
            get_string("description", "mod_ilddigitalcert"),
            'wrap="virtual" rows="5" cols="50"'
        );

        // Template.
        $mform->addElement(
            'editor',
            'template',
            get_string('template', 'mod_ilddigitalcert'),
            array('element_type' => 'htmleditor')
        );
        $mform->setType('template', PARAM_RAW);

        // Image.
        $filemanageroptions = array();
        $filemanageroptions['accepted_types'] = 'image';
        $filemanageroptions['maxbytes'] = 0;
        $filemanageroptions['maxfiles'] = 1;
        $mform->addElement('filemanager', 'image', get_string("image", "mod_ilddigitalcert"), null, $filemanageroptions);

        // Badge criteria.
        $mform->addElement(
            'textarea',
            'criteria',
            get_string("criteria", "mod_ilddigitalcert"),
            'wrap="virtual" rows="5" cols="50"'
        );
        // Badge expertise.
        $mform->addElement(
            'textarea',
            'expertise',
            get_string("expertise", "mod_ilddigitalcert"),
            'wrap="virtual" rows="5" cols="50"'
        );

        // Expire date.
        $mform->addElement(
            'date_selector',
            'expiredate', get_string('expiredate', 'mod_ilddigitalcert'),
            array('startyear' => date('Y', time()),
                'stopyear' => intval(date('Y', time())) + 10,
                'optional' => true
            )
        );
        // Expire period.
        $mform->addElement(
            'duration',
            'expireperiod',
            get_string('expireperiod', 'mod_ilddigitalcert'),
            array('optional' => true)
        );

        // Examination start/end.
        $mform->addElement(
            'date_selector',
            'examination_start',
            get_string('examination_start', 'mod_ilddigitalcert'),
            array(
                'startyear' => date('Y', time()),
                'stopyear' => intval(date('Y', time())) + 10,
                'optional' => true
            )
        );
        $mform->addElement(
            'date_selector',
            'examination_end',
            get_string('examination_end', 'mod_ilddigitalcert'),
            array(
                'startyear' => date('Y', time()),
                'stopyear' => intval(date('Y', time())) + 10,
                'optional' => true
            )
        );
        // Examination place.
        $mform->addElement(
            'text',
            'examination_place',
            get_string('examination_place', 'mod_ilddigitalcert'),
            array('size' => '64')
        );
        $mform->setType('examination_place', PARAM_TEXT);
        // Examination regulations.
        $mform->addElement(
            'text',
            'examination_regulations',
            get_string('examination_regulations', 'mod_ilddigitalcert'),
            array('size' => '64')
        );
        $mform->setType('examination_regulations', PARAM_TEXT);
        // Examination regulations url.
        $mform->addElement(
            'text',
            'examination_regulations_url',
            get_string('examination_regulations_url', 'mod_ilddigitalcert'),
            array('size' => '64')
        );
        $mform->setType('examination_regulations_url', PARAM_TEXT);
        // Examination regulations_id.
        $mform->addElement(
            'text',
            'examination_regulations_id',
            get_string('examination_regulations_id', 'mod_ilddigitalcert'),
            array('size' => '64')
        );
        $mform->setType('examination_regulations_id', PARAM_TEXT);
        // Examination regulations_date.
        $mform->addElement(
            'date_selector',
            'examination_regulations_date',
            get_string('examination_regulations_date', 'mod_ilddigitalcert'),
            array(
                'startyear' => intval(date('Y', time())) - 10,
                'stopyear' => intval(date('Y', time())) + 10,
                'optional' => true
            )
        );

        // Automation and process configuration section.
        $mform->addElement('header', 'automationheader', get_string('automation', 'mod_ilddigitalcert'));
        $mform->setExpanded('automationheader');

        // Enable automation checkbox.
        $mform->addElement('advcheckbox', 'automation', get_string('automation', 'mod_ilddigitalcert'),
            get_string('enable'), array('group' => 1), array(0, 1));
        $mform->addHelpButton('automation', 'automation', 'mod_ilddigitalcert');

        // Get Certifiers that are enroled in the course.
        if (is_object($this->get_course())) {
            $courseid = ((object) $this->get_course())->id;
        } else {
            $courseid = $this->get_course();
        }
        $certifiers = get_certifiers($courseid);
        $autocertifiers = array('a' => get_string('choose', 'mod_ilddigitalcert'));
        foreach ($certifiers as $certifier) {
            $autocertifiers[$certifier->id] = $certifier->firstname . ' ' . $certifier->lastname;
        }

        if (!$autocertifiers) {
            $autocertifiers = array('a' => get_string('no_certifier', 'mod_ilddigitalcert'));
        }

        // Choose moodle-user that is responsible for certification.
        $mform->addElement('select', 'auto_certifier', get_string('certifier', 'mod_ilddigitalcert'), $autocertifiers);
        $mform->addHelpButton('auto_certifier', 'auto_certifier', 'mod_ilddigitalcert');
        $mform->setDefault('auto_certifier', 'a');

        // Sets the private key for the set certifier.
        $mform->addElement('password', 'auto_pk', get_string('auto_pk', 'mod_ilddigitalcert'));
        $mform->addHelpButton('auto_pk', 'auto_pk', 'mod_ilddigitalcert');

        // Enable weekly automation report.
        $mform->addElement('advcheckbox', 'automation_report', get_string('automation_report:enable', 'mod_ilddigitalcert'),
            get_string('enable'), array('group' => 1), array(0, 1));
        $mform->addHelpButton('automation_report', 'automation_report:enable', 'mod_ilddigitalcert');
        $mform->setDefault('automation_report', 1);

        // DC connector settings
        $mform->addElement('header', 'dcconnectorheader', get_string('dcconnectorsettings', 'mod_ilddigitalcert'));

        $mform->addElement(
            'textarea',
            'dcattributes',
            get_string('dcattributes', 'mod_ilddigitalcert'),
            'wrap="virtual" rows="5" cols="50"'
        );

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('image');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_ilddigitalcert', 'content', 0, array());
            $defaultvalues['image'] = $draftitemid;

            $defaultvalues['template'] = array('text' => $defaultvalues['template'], 'format' => FORMAT_HTML);

            // Decrypts the pk if already set to prevent double encryption on save.
            if ($defaultvalues['auto_pk']) {
                $this->pk = $defaultvalues['auto_pk'];
                $defaultvalues['auto_pk'] = self::ENCRYPTED_PREFIX;
            }
        }
    }

    public function get_data() {
        $data = parent::get_data();

        if (empty($data)) {
            return false;
        }
        $data->template = $data->template['text'];
        file_save_draft_area_files($data->image, $this->context->id, 'mod_ilddigitalcert', 'content',
                   0, array('maxfiles' => 1));

        // Make sure only valid userids are stored in db.
        if (!is_numeric($data->auto_certifier) || $data->auto_certifier <= 0) {
            $data->auto_certifier = null;
        }

        // If automation is disabled, unset pk.
        if (!$data->automation) {
            $data->auto_pk = null;
        }

        // If pk is set, encrypt it to protect from non trustworthy db user.
        if (!empty($data->auto_pk)) {
            if ($data->auto_pk == self::ENCRYPTED_PREFIX) {
                $data->auto_pk = $this->pk;
            } else {
                $data->auto_pk = \mod_ilddigitalcert\crypto_manager::encrypt($data->auto_pk);
            }
        }

        return $data;
    }
}
