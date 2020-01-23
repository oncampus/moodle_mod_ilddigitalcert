<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class edit_certifiers_form extends moodleform {
    //Add elements to form
    function definition() {
        global $CFG;

        $mform = $this->_form; // Don't forget the underscore!
		
		

        $this->add_action_buttons(true, get_string('save'));

    }

    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
	
	
	function data_preprocessing(&$default_values) {
		//print_object($this->_customdata); die();
		
	}
}