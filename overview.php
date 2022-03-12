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
 * Prints an overview of all certificates a student has received.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2022, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once('locallib.php');

$id = optional_param('id', null, PARAM_INT);
$cert_json = optional_param('cert_json', null, PARAM_NOTAGS);
$download = optional_param('download', '', PARAM_ALPHA);
$template_data = array();

if($id) {
    list($course, $cm) = get_course_and_cm_from_cmid($id, 'ilddigitalcert');

    require_login($course, true, $cm);
    
    $moduleinstance = $DB->get_record('ilddigitalcert', array('id' => $cm->instance), '*', MUST_EXIST);
    $context = context_module::instance($cm->id);
    $PAGE->set_title(format_string($moduleinstance->name));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->set_url('/mod/ilddigitalcert/overview.php?id=' . $id);
} else {
    require_login();
    
    $context = context_system::instance();
    $PAGE->set_context($context);
    $PAGE->set_title(format_string(get_string('pluginname', 'mod_ilddigitalcert')));
    $PAGE->set_heading(get_string('pluginname', 'mod_ilddigitalcert'));
    $PAGE->set_url('/mod/ilddigitalcert/overview.php');
}

if (isguestuser()) {
    redirect(new moodle_url('/login/'));
}

$has_cap_viewall = has_capability('moodle/grade:viewall', $context);
$has_cap_certify = get_user_preferences('mod_ilddigitalcert_certifier', false, $USER) ? true : false;

$template_data['has_cap_viewall'] = $has_cap_viewall;
$template_data['has_cap_certify'] = $has_cap_certify;

$PAGE->requires->js(new moodle_url('/mod/ilddigitalcert/js/pk_form.js'));

if($has_cap_certify) {
    // Reissue selected certificates.
    // Instantiate reissue form.
    $reissue_form = new \mod_ilddigitalcert\output\form\reissue_form(qualified_me());
    // Set default data.
    if (!$reissue_form->get_data()) {
        $reissue_form_data = (object) [
            'selected' => '[]',
        ];

        // Set default data (if any).
        $reissue_form->set_data($reissue_form_data);
    } else {
        $reissue_form->action();
    }

    // Set template data needed for rendering the $reissue_form.
    $template_data['reissue_form'] = $reissue_form->render();


    // Sign and register selected certificates in the blockchain.
    // Instantiate to_blockchain form.
    $to_bc_form = new \mod_ilddigitalcert\output\form\to_blockchain_form(qualified_me());

    // Set default data.
    if (!$to_bc_form->get_data()) {
        $to_bc_form_data = (object) [
            'selected' => '[]',
            'pk' => '',
        ];

        // Set default data (if any).
        $to_bc_form->set_data($to_bc_form_data);
    } else {
        $to_bc_form->action();
    }

    // Set template data needed for rendering the $to_bc_form.
    $template_data['to_bc_form'] = $to_bc_form->render();    
    
    // Revoke selected certificates.
    // Instantiate revocation form.
    $revocation_form = new \mod_ilddigitalcert\output\form\revocation_form(qualified_me());

    // Set default data.
    if (!$revocation_form->get_data()) {
        $revocation_form_data = (object) [
            'selected' => '[]',
            'pk' => '',
        ];

        // Set default data (if any).
        $revocation_form->set_data($revocation_form_data);
    } else {
        $revocation_form->action();
    }

    // Set template data needed for rendering the $to_bc_form.
    $template_data['revocation_form'] = $revocation_form->render();
}

// Instantiate search form.
$search_form = new \mod_ilddigitalcert\output\form\search_certificates_form(qualified_me());
// Set default data (if any).
if (!$search_form->get_data()) {
    $search_form_data = (object) [
        'search_query' => '',
        'search_filter' => '',
    ];

    if(isset($course)) {
        $search_form_data->courseid = $course->id;
    }

    if(!$has_cap_viewall) {
        $search_form_data->userid = $USER->id;
    }
    
    $search_form->set_data($search_form_data);
}

// Get selected certificates.
 else { // Get search results;
    list($conditions, $params) = $search_form->action();
}

// Else get all certificates.
if (!isset($conditions)) {
    $conditions = '';
    $params = array();
    if(isset($course)) {
        $conditions = ' AND courseid = :courseid';
        $params['courseid'] = $course->id;
    }
    if(!$has_cap_viewall) {
        $conditions = ' AND userid = :userid';
        $params['userid'] = $USER->id;
    }
    
    if ($cert_json) {
        $cert_ids = json_decode($cert_json);
        
        if(isset($cert_ids) && !empty($cert_ids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($cert_ids, SQL_PARAMS_NAMED);
            $conditions .= " AND idci.id $insql";
            $params = array_merge($params, $inparams);

            print_r($conditions);
            print_r($params);
        }
    }
}

// Set template data needed for rendering the $search_form.
$template_data['search_form'] = $search_form->render();


// Build page.
echo $OUTPUT->header();

if($id) {
    echo $OUTPUT->heading(get_string('overview_certifier', 'mod_ilddigitalcert'));

    $template_data ['certificate_name'] = $moduleinstance->name;
    $template_data ['preview_url'] = (new moodle_url('/mod/ilddigitalcert/view.php', array("id" => $id, 'view' => "preview")))->out(false);
    $template_data ['course_name'] = $course->fullname;
} else {
    if($has_cap_viewall) {
        echo $OUTPUT->heading(get_string('overview_certifier', 'mod_ilddigitalcert'));
    } else {
        echo $OUTPUT->heading(get_string('overview', 'mod_ilddigitalcert'));
    }
}

echo $OUTPUT->render_from_template('mod_ilddigitalcert/overview', $template_data);    

// Build certificate table that shows search results.
$table = new \mod_ilddigitalcert\output\table\certificate_table(
    'cert_table' . '_' . date('now'),
    $has_cap_certify,
    isset($course) ? $course->id : null,
    $has_cap_viewall ? null : $USER->id
);

// Set table sql.
$select = 'idci.id AS certid, idci.name, idci.cmid, idci.enrolmentid, idci.timecreated as issued_on, idci.certhash as status, 
    u.*, c.id AS courseid, c.shortname AS courseshortname';
$from = '{ilddigitalcert_issued} idci, {user} u, {course} c';
$where = 'u.id = idci.userid AND  c.id = idci.courseid';
$table->set_sql($select, $from, $where . $conditions, $params);

$table->define_baseurl(qualified_me());

$table->out(10, false);

echo $OUTPUT->footer();
