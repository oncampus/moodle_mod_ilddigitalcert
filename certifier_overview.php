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
 * @copyright   2023, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once('locallib.php');
require_once(__DIR__ . '/search_certificates_form.php');
require_once(__DIR__ . '/to_blockchain_form.php');
require_once(__DIR__ . '/reissue_form.php');

$cert_json = optional_param('cert_json', null, PARAM_NOTAGS);

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/ilddigitalcert/overview.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title(format_string(get_string('pluginname', 'mod_ilddigitalcert')));
$PAGE->set_heading(get_string('pluginname', 'mod_ilddigitalcert'));

require_login();

if (isguestuser()) {
    redirect($CFG->wwwroot . '/login/');
}

if (!get_user_preferences('mod_ilddigitalcert_certifier', false, $USER)) {
    redirect($CFG->wwwroot . '/mod/ilddigitalcert/overview.php');
}

$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/ilddigitalcert/js/pk_form.js'));

// Build page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('overview', 'mod_ilddigitalcert'));

// Reissue selected certificates.

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

                    list($course, $cm) = get_course_and_cm_from_cmid($certificate->cmid, 'ilddigitalcert');
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
    }
} else {
    $reissue_form_data = (object) [
        'action' => 'reissue',
        'selected' => '[]',
    ];
}

// Set default data (if any).
$reissue_form->set_data($reissue_form_data);


// Sign and register selected certificates in the blockchain.
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
            FROM {ilddigitalcert_issued} idci, {user} u, {course} c
            WHERE u.id = idci.userid
            AND  c.id = idci.courseid';

        $params = array();

        if ($search_form_data->search_query !== '') {
            $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
            $sql .= ' AND (' . $DB->sql_like($fullname, ':search1', false, false) . '
            OR ' . $DB->sql_like('c.shortname', ':search2', false, false) . '
            OR ' . $DB->sql_like('c.fullname', ':search3', false, false) . '
                OR ' . $DB->sql_like('idci.name', ':search4', false, false) . ')';
            $params['search1'] = '%' . $search_form_data->search_query . '%';
            $params['search2'] = '%' . $search_form_data->search_query . '%';
            $params['search3'] = '%' . $search_form_data->search_query . '%';
            $params['search4'] = '%' . $search_form_data->search_query . '%';
        }

        if ($search_form_data->search_filter === 'only_bc') {
            $sql .= ' AND idci.certhash is not null ';
        } else if ($search_form_data->search_filter === 'only_nonbc') {
            $sql .= ' AND idci.certhash is null ';
        }

        $issuedcertificates = $DB->get_records_sql($sql, $params);
    } else {
        if ($cert_json) {
            $cert_list = json_decode($cert_json);

            list($insql, $inparams) = $DB->get_in_or_equal($cert_list);
            $sql = "SELECT * FROM {ilddigitalcert_issued} WHERE id $insql";
            $issuedcertificates = $DB->get_records_sql($sql, $inparams);
        } else {
            $issuedcertificates = $DB->get_records('ilddigitalcert_issued');
        }
    }
} else {
    if ($cert_json) {
        $cert_list = json_decode($cert_json);

        list($insql, $inparams) = $DB->get_in_or_equal($cert_list);
        $sql = "SELECT * FROM {ilddigitalcert_issued} WHERE id $insql";
        $issuedcertificates = $DB->get_records_sql($sql, $inparams);
    } else {
        $issuedcertificates = $DB->get_records('ilddigitalcert_issued');
    }

    $search_form_data = (object) [
        'search_query' => '',
        'search_filter' => ''
    ];
}

// Set default data (if any).
$search_form->set_data($search_form_data);

$template_data = array(
    'to_bc_form' => $to_bc_form->render(),
    'reissue_form' => $reissue_form->render(),
    'search_form' => $search_form->render(),
    'search_count' => count($issuedcertificates),
    'certs_table' => \mod_ilddigitalcert\manager::render_certs_table($issuedcertificates, true),
);

echo $OUTPUT->render_from_template('mod_ilddigitalcert/teacher_view', $template_data);

echo $OUTPUT->footer();
