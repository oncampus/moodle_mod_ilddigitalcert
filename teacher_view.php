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
 * This page allows a teacher to view all certificats or search for specific certificats.
 * The teacher can also view a single certificate, or sign and register them in the blockchain or to reissue them in bulk or indiviually.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once('locallib.php');
require_once(__DIR__ . '/search_certificates_form.php');
require_once(__DIR__ . '/to_blockchain_form.php');
require_once(__DIR__ . '/reissue_form.php');

$id = required_param('id', PARAM_INT);
$ueid = optional_param('ueid', 0, PARAM_INT);
$view = optional_param('view', 'html', PARAM_RAW);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'ilddigitalcert');

require_login($course, true, $cm);


if (!has_capability('moodle/grade:viewall', context_course::instance($course->id))) {
    redirect($CFG->wwwroot . '/mod/ilddigitalcert/view.php?id=' . $cm->id . '&ueid=' . $ueid);
}

$moduleinstance = $DB->get_record('ilddigitalcert', array('id' => $cm->instance), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

$PAGE->set_url($CFG->wwwroot . '/mod/ilddigitalcert/teacher_view.php', array('id' => $cm->id, 'ueid' => 0));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/ilddigitalcert/js/pk_form.js'));

// Reissue selected certificats.

// Instantiate reissue form.
$reissue_form = new mod_ilddigialcert_reissue_form();
if ($reissue_form_data = $reissue_form->get_data()) {
    $selected_certs = json_decode($reissue_form_data->selected);
    if (!empty($selected_certs)) {
        // Get certificate records from selected ids.
        list($insql, $inparams) = $DB->get_in_or_equal($selected_certs);
        $sql = "SELECT * FROM {ilddigitalcert_issued} WHERE txhash IS NULL AND id $insql";
        $certificates = $DB->get_records_sql($sql, $inparams);

        if ($reissue_form_data->action == 'reissue') { // Write selected certificates to blockchain
            $message = '';
            foreach ($certificates as $certificate) {
                if ($reissueuser = $DB->get_record('user', array('id' => $certificate->userid, 'confirmed' => 1, 'deleted' => 0))) {
                    $certmetadata = generate_certmetadata($cm, $reissueuser);
                    reissue_certificate($certmetadata, $certificate->userid, $cm->id);

                    $recipient = $certmetadata->{'extensions:recipientB4E'};
                    $recipientname = $recipient->givenname . ' ' . $recipient->surname;
                    $message .= '<p>Susscessfully reissued certificate for: <b>' . $recipientname . '</b></p><br/>';
                }
            }

            if ($message) {
                \core\notification::success($message);
            }

            $invalid_count = count($selected_certs) - count($certificates);
            if ($invalid_count > 0) {
                \core\notification::warning("Couldn't reissue $invalid_count certificat(s), because they where already signed and registered in the blockchain.");
            }
        }
    } else {
        print_r('empty slected');
    }
} else {
    print_r('no reissue form data');
    $reissue_form_data = (object) [
        'id' => $id,
        'ueid' => $ueid,
        'action' => 'reissue',
        'selected' => '[]',
    ];
}

// Set default data (if any).
$reissue_form->set_data($reissue_form_data);


// Sign and register selected certificats in the blockchain.
// Instantiate to_blockchain form.
$to_bc_form = new mod_ilddigialcert_to_blockchain_form();

if ($to_bc_form_data = $to_bc_form->get_data()) {
    $selected_certs = json_decode($to_bc_form_data->selected);
    if (!empty($selected_certs)) {
        // Get certificate records from selected ids.
        list($insql, $inparams) = $DB->get_in_or_equal($selected_certs);
        $sql = "SELECT * FROM {ilddigitalcert_issued} WHERE txhash IS NULL AND id $insql";
        $certificates = $DB->get_records_sql($sql, $inparams);

        if ($to_bc_form_data->action == 'toblockchain') { // Write selected certificates to blockchain
            $message = '';
            // Write every cert to the blockchain with the given private key.
            foreach ($certificates as $issuedcertificate) {
                if (to_blockchain($issuedcertificate, $USER, $to_bc_form_data->pk)) {
                    $recipient = json_decode($issuedcertificate->metadata)->{'extensions:recipientB4E'};
                    $recipientname = $recipient->givenname . ' ' . $recipient->surname;
                    $message .= '<br/><div><p>' . get_string('registered_and_signed', 'mod_ilddigitalcert') . '</p>';
                    $message .= '<p>Recipient: <b>' . $recipientname . '</b><br/>';
                    $message .= 'Hash: <b>' . $issuedcertificate->certhash . '</b><br/>';
                    $message .= 'Startdate: <b>' . json_decode($issuedcertificate->metadata)->issuedOn . '</b><br/>';
                    $message .= 'Enddate: <b>' . json_decode($issuedcertificate->metadata)->expires . '</b></p></div><br/>';
                } else {
                    print_error(
                        'error_register_cert',
                        'mod_ilddigitalcert',
                        new moodle_url('/mod/ilddigitalcert/view.php', array('id' => $id))
                    );
                }
            }

            if ($message) {
                \core\notification::success($message);
            }

            $invalid_count = count($selected_certs) - count($certificates);
            if ($invalid_count > 0) {
                \core\notification::warning("Couldn't sign $invalid_count certificat(s), because they where already signed and registered in the blockchain.");
            }
        }
    }
} else {
    $to_bc_form_data = (object) [
        'id' => $id,
        'ueid' => $ueid,
        'action' => 'toblockchain',
        'selected' => '[]',
        'pk' => '',
    ];
}

// Set default data (if any).
$to_bc_form->set_data($to_bc_form_data);



// Instantiate search form.
$search_form = new mod_ilddigialcert_search_certificates_form();

// Get search results.
$issuedcertificates = array();
if ($search_form_data = $search_form->get_data()) {
    if ($search_form_data->search_query || $search_form_data->search_filter) {
        $sql = 'SELECT idci.*
            FROM {ilddigitalcert_issued} idci, {user} u
            WHERE idci.courseid = :courseid
            AND u.id = idci.userid';

        $params = array('courseid' => $course->id);

        if ($search_form_data->search_query !== '') {
            $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
            $sql .= ' AND (' . $DB->sql_like($fullname, ':search1', false, false) . '
                OR ' . $DB->sql_like('idci.name', ':search2', false, false) . ')';
            $params['search1'] = '%' . $search_form_data->search_query . '%';
            $params['search2'] = '%' . $search_form_data->search_query . '%';
        }

        if ($search_form_data->search_filter === 'only_bc') {
            $sql .= ' AND idci.certhash is not null ';
        } else if ($search_form_data->search_filter === 'only_nonbc') {
            $sql .= ' AND idci.certhash is null ';
        }

        $issuedcertificates = $DB->get_records_sql($sql, $params);
    } else {
        $issuedcertificates = $DB->get_records('ilddigitalcert_issued', array('courseid' => $course->id));
    }
} else {
    $issuedcertificates = $DB->get_records('ilddigitalcert_issued', array('courseid' => $course->id));

    $search_form_data = (object) [
        'id' => $id,
        'ueid' => $ueid,
        'search_query' => '',
        'search_filter' => ''
    ];
}

// Set default data (if any).
$search_form->set_data($search_form_data);



// Build page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'mod_ilddigitalcert'));

$template_data = array(
    'certificate_name' => $moduleinstance->name,
    'course_name' => $course->fullname,
    'to_bc_form' => $to_bc_form->render(),
    'reissue_form' => $reissue_form->render(),
    'search_form' => $search_form->render(),
    'search_count' => count($issuedcertificates),
    'certs_table' => mod_ilddigitalcerts_render_certs_table($issuedcertificates, $course->id, $ueid),
);

echo $OUTPUT->render_from_template('mod_ilddigitalcert/teacher_view', $template_data);

echo $OUTPUT->footer();
