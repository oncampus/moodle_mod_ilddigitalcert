<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class edit_issuers_form extends moodleform {
    //Add elements to form
    function definition() {
        global $CFG;

        $mform = $this->_form; // Don't forget the underscore!
		
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
		
		//image
		$filemanager_options = array();
        //$filemanager_options['accepted_types'] = '*.svg, *.png, *.jpg, *.gif';
		$filemanager_options['accepted_types'] = 'image';
        $filemanager_options['maxbytes'] = 0;
        $filemanager_options['maxfiles'] = 1;
		$mform->addElement('filemanager', 'issuerimage', get_string("image", "mod_ilddigitalcert"), null, $filemanager_options);
                
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
        //$mform->addElement('static', 'projecturldescr', '', get_string('ildazweiprojecturldescr', 'mod_ildazwei'));

        $mform->addElement('text', 'issuerzip', get_string('issuerzip', 'mod_ilddigitalcert'));
        $mform->setType('issuerzip', PARAM_INT);
		
		$mform->addElement('text', 'issuerstreet', get_string('issuerstreet', 'mod_ilddigitalcert'), $attributes);
        $mform->setType('issuerstreet', PARAM_RAW);
		
		$mform->addElement('text', 'issuerpob', get_string('issuerpob', 'mod_ilddigitalcert'));
        $mform->setType('issuerpob', PARAM_INT);

        $this->add_action_buttons(true, get_string('save'));

    }

    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
	
	
	function data_preprocessing(&$default_values) {
		//print_object($this->_customdata); die();
		if (isset($this->_customdata['issuerid']) and $this->_customdata['issuerid'] > 0) {
			if ($this->current->instance) {
				$draftitemid = file_get_submitted_draft_itemid('issuerimage');
				$context = context_system::instance();
				file_prepare_draft_area($draftitemid, $context->id, 'mod_ilddigitalcert', 'issuer', $this->_customdata['issuerid'], array());
				$default_values['issuerimage'] = $draftitemid;
			}
		}
	}
}