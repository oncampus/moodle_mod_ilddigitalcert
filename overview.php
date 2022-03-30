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
$certjson = optional_param('cert_json', null, PARAM_NOTAGS);
$download = optional_param('download', '', PARAM_ALPHA);
$templatedata = array();

if ($id) {
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

$hascapviewall = has_capability('moodle/grade:viewall', $context);
$hascapcertify = get_user_preferences('mod_ilddigitalcert_certifier', false, $USER) ? true : false;

$templatedata['has_cap_viewall'] = $hascapviewall;
$templatedata['has_cap_certify'] = $hascapcertify;

$PAGE->requires->js(new moodle_url('/mod/ilddigitalcert/js/pk_form.js'));

if ($hascapcertify) {
    // Reissue selected certificates.
    // Instantiate reissue form.
    $reissueform = new \mod_ilddigitalcert\output\form\reissue_form(qualified_me());
    // Set default data.
    if (!$reissueform->get_data()) {
        $reissueformdata = (object) [
            'selected' => '[]',
        ];

        // Set default data (if any).
        $reissueform->set_data($reissueformdata);
    } else {
        $reissueform->action();
    }

    // Set template data needed for rendering the $reissue_form.
    $templatedata['reissue_form'] = $reissueform->render();


    // Sign and register selected certificates in the blockchain.
    // Instantiate to_blockchain form.
    $tobcform = new \mod_ilddigitalcert\output\form\to_blockchain_form(qualified_me());

    // Set default data.
    if (!$tobcform->get_data()) {
        $tobcformdata = (object) [
            'selected' => '[]',
            'pk' => '',
        ];

        // Set default data (if any).
        $tobcform->set_data($tobcformdata);
    } else {
        $tobcform->action();
    }

    // Set template data needed for rendering the $to_bc_form.
    $templatedata['to_bc_form'] = $tobcform->render();

    // Revoke selected certificates.
    // Instantiate revocation form.
    $revocationform = new \mod_ilddigitalcert\output\form\revocation_form(qualified_me());

    // Set default data.
    if (!$revocationform->get_data()) {
        $revocationformdata = (object) [
            'selected' => '[]',
            'pk' => '',
        ];

        // Set default data (if any).
        $revocationform->set_data($revocationformdata);
    } else {
        $revocationform->action();
    }

    // Set template data needed for rendering the $to_bc_form.
    $templatedata['revocation_form'] = $revocationform->render();
}

// Instantiate search form.
$searchform = new \mod_ilddigitalcert\output\form\search_certificates_form(qualified_me());
// Set default data (if any).
if (!$searchform->get_data()) {
    $searchformdata = (object) [
        'search_query' => '',
        'search_filter' => '',
    ];

    if (isset($course)) {
        $searchformdata->courseid = $course->id;
    }

    if (!$hascapviewall) {
        $searchformdata->userid = $USER->id;
    }

    $searchform->set_data($searchformdata);
} else {
    // Get search results.
    list($conditions, $params) = $searchform->action();
}

// Else get all certificates.
if (!isset($conditions)) {
    $conditions = '';
    $params = array();
    if (isset($course)) {
        $conditions = ' AND courseid = :courseid';
        $params['courseid'] = $course->id;
    }
    if (!$hascapviewall) {
        $conditions = ' AND userid = :userid';
        $params['userid'] = $USER->id;
    }

    if ($certjson) {
        $certids = json_decode($certjson);

        if (isset($certids) && !empty($certids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($certids, SQL_PARAMS_NAMED);
            $conditions .= " AND idci.id $insql";
            $params = array_merge($params, $inparams);
        }
    }
}

// Set template data needed for rendering the $search_form.
$templatedata['search_form'] = $searchform->render();


// Build page.
echo $OUTPUT->header();

if ($id) {
    echo $OUTPUT->heading(get_string('overview_certifier', 'mod_ilddigitalcert'));

    $templatedata['certificate_name'] = $moduleinstance->name;
    $templatedata['preview_url'] = (
        new moodle_url(
            '/mod/ilddigitalcert/view.php',
            array("id" => $id, 'view' => "preview")
        )
    )->out(false);
    $templatedata['course_name'] = $course->fullname;
} else {
    if ($hascapviewall) {
        echo $OUTPUT->heading(get_string('overview_certifier', 'mod_ilddigitalcert'));
    } else {
        echo $OUTPUT->heading(get_string('overview', 'mod_ilddigitalcert'));
    }
}

echo $OUTPUT->render_from_template('mod_ilddigitalcert/overview', $templatedata);

// Build certificate table that shows search results.
$table = new \mod_ilddigitalcert\output\table\certificate_table(
    'cert_table' . '_' . time(),
    $hascapcertify,
    isset($course) ? $course->id : null,
    $hascapviewall ? null : $USER->id
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
