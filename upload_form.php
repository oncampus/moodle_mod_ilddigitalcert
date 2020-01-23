<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class upload_form extends moodleform {
    //Add elements to form
    function definition() {
        global $CFG;

        $mform = $this->_form; // Don't forget the underscore!
		
		$mform->addElement('filepicker', 'certfile', get_string('file'), null,
                   array('maxbytes' => 1100000, 'accepted_types' => '*.json,*.pdf'));

        $this->add_action_buttons(false, get_string('upload'));

    }

    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}