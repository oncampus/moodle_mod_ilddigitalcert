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
 * Internal library of functions for module ilddigitalcert
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/vendor/autoload.php');

use mod_ilddigitalcert\bcert\certificate;
use mod_ilddigitalcert\web3_manager;
use mod_ilddigitalcert\certificate_manager;

/**
 * Initiates and controls the download of the certificate in the requested format.
 * The certificate can be downloaded as .bcrt file in obenBadge format, .xml file in edci format or as a pdf.
 *
 * @param string $icid itemid usually corresponding row id of database table.
 * @param string $download Controls what kind of file gets sent to the user. Expected values are 'json', 'edci' and 'pdf'.
 */
function download_json($icid, $download) {
    global $DB;

    // Prepare certificate data for download.
    $certificaterecord = $DB->get_record('ilddigitalcert_issued', array('id' => $icid), '*', MUST_EXIST);
    $metacertificate = certificate::from_ob($certificaterecord->metadata);

    list($storedbcrt, $storededci) = get_certificate_files($certificaterecord, $metacertificate);

    if ($download == 'json') {
        send_stored_file($storedbcrt, null, 0, true);
    } else if ($download == 'edci') {
        send_stored_file($storededci, null, 0, true);
    } else if ($download == 'pdf') {
        $pdf = create_pdf_certificate($certificaterecord, $metacertificate);
        // Start download of the pdf file.
        $pdf->Output(get_certificate_name($certificaterecord, $metacertificate) .'.pdf', 'I');

        return;
    }
}
/**
 * Retrieves the .bcrt and .xml files containing the OpenBadge and EDCI certificate metadata from the filesystem.
 *
 * @param stdClass $certificaterecord
 * @param certificate $metacertificate
 * @return Mpdf\Mpdf
 */
function create_pdf_certificate($certificaterecord, $metacertificate) {
    // Get stored certificate metadata.
    list($storedbcrt, $storededci) = get_certificate_files($certificaterecord, $metacertificate);

    // Read file content.
    $content = $storedbcrt->get_content();

    $pdf = new Mpdf\Mpdf([
        'mode' => 'utf-8',
        'margin_top' => 0,
        'margin_left' => 0,
        'margin_right' => 0,
        'margin_bottom' => 0,
        'format' => [210, 297]
    ]);
    $pdf->showImageErrors = true;

    // Decode the assertionpage info included in the ob certificate and write it as html to the pdf.
    $html = '<h1>Error</h1>';

    if (isset($content) and $content != '') {
        $jsonobj = json_decode($content);
        $html = base64_decode($jsonobj->{'extensions:assertionpageB4E'}->assertionpage);
    }

    $pdf->WriteHTML($html);

    // Generate hash.
    // Add salt to openBadge cert.
    $salt = get_token($certificaterecord->institution_token);
    $hash = $metacertificate->get_ob_hash($salt);
    // Generate pdf footer section including the hash value of the ob certificate.
    $pdf->WriteHTML(get_pdf_footerhtml($hash));

    $filename = get_certificate_name($certificaterecord, $metacertificate);

    // Add openBadge and edci files as attachements to the pdf.
    $associatedfiles = [
        [
            'name' => $filename.'.bcrt',
            'mime' => 'application/json',
            'description' => 'some description',
            'AFRelationship' => 'Alternative',
            'path' => new moodle_url('/mod/ilddigitalcert/download_pdf.php', array('id' => $storedbcrt->get_id())),
        ]
    ];

    if ($storededci) {
        array_push($associatedfiles, [
            'name' => $filename.'.xml',
            'mime' => 'application/xml',
            'description' => 'some description',
            'AFRelationship' => 'Alternative',
            'path' => new moodle_url('/mod/ilddigitalcert/download_pdf.php', array('id' => $storededci->get_id())),
        ]);
    }

    $pdf->SetAssociatedFiles($associatedfiles);
    return $pdf;
}

/**
 * Generates a html footer section for use in a pdf certificate. Includes a qr code and hash that enables verifying the certificate.
 *
 * @param string $hash Hash value of an openBadge certificate.
 * @return string
 */
function get_pdf_footerhtml($hash) {
    global $CFG;

    $verifyurl = $CFG->wwwroot . '/mod/ilddigitalcert/verify.php';

    $html = '
        <div style="border: 0px solid #000;padding-top: 15px;padding-left: 8px;position:absolute;top:975px;left:0px;">
        <table class="items" width="50%" cellpadding="3" border="0">
                <tr>
                    <td class="barcodecell" width="100px">
                        <a href="' . $verifyurl . '?hash=' . $hash . '" style="color: rgb(0,0,0) !important;">
                            <div>
                                <barcode code="' . $CFG->wwwroot . '/mod/ilddigitalcert/verify.php?hash=' .
        $hash . '" type="QR" class="barcode" size="1" error="M" disableborder="1" />
                            </div>
                        </a>
                    </td>
                    <td style="font-family: verdana;font-size: 7pt;">' .
        '<b>' . get_string('verify_authenticity', 'mod_ilddigitalcert') . '</b><br/><br/>' .
        get_string('verify_authenticity_descr', 'mod_ilddigitalcert', array('url' => $verifyurl, 'hash' => $hash)) . '
                    </td>
                </tr>
        </table>
        </div>';
    return $html;
}

/**
 * Creates a random hexadecimal string and writes it to a file
 * located in $CFG->dataroot.'/ilddigitalcert_data.
 *
 * @return string|bool Returns the identifier of the file the token was wrtten to or false if the tokenfile couldn't be created.
 */
function save_token() {
    global $CFG;
    try {
        if (!is_dir($CFG->dataroot . '/ilddigitalcert_data')) {
            if (!mkdir($CFG->dataroot . '/ilddigitalcert_data', 0775)) {
                return false;
            }
        }
        $id = uniqid();
        $filename = $CFG->dataroot . '/ilddigitalcert_data/' . $id;
        while (file_exists($filename)) {
            $id = uniqid();
            $filename = $CFG->dataroot . '/ilddigitalcert_data/' . $id;
        }
        $token = bin2hex(random_bytes(32));
        if (!file_put_contents($filename, $token)) {
            return false;
        }
    } catch (Exception $e) {
        return false;
    }
    return $id;
}

/**
 * Retrieves the contents of the token file located in $CFG->dataroot.'/ilddigitalcert_data/
 * with the identifier of $tokenid.
 *
 * @param string $tokenid identifier of the file the token was written to.
 * @return string|bool Returns the token or false if the token could't be found.
 */
function get_token($tokenid) {
    global $CFG;
    $filename = $CFG->dataroot . '/ilddigitalcert_data/' . $tokenid;
    if (file_exists($filename)) {
        return file_get_contents($filename);
    } else {
        return false;
    }
}

/**
 * Creates a sha256 hash value of the json encoded metadata by sorting the fields alphabetically.
 * Also removes verification data, before calculating the hash.
 *
 * @param string $metadatajson Json encoded metadata of a certificate.
 * @return string Returns a hash.
 */
function calculate_hash($metadatajson) {
    $metadata = json_decode($metadatajson);
    $metadata = sort_obj($metadata);
    $metadata->recipient->hashed = false; // TODO ???
    // Remove verification (if exists already).
    unset($metadata->{'extensions:verifyB4E'});
    unset($metadata->{'verification'}); // For downward compatibility.
    $metadatajson = json_encode($metadata, JSON_UNESCAPED_SLASHES);
    $hash = '0x'.hash('sha256', $metadatajson);
    return $hash;
}

/**
 * Sorts object properties and their children alphabetically.
 *
 * @param object $obj Object to be sorted.
 * @return object Returns a sorted object.
 */
function sort_obj($obj) {
    $arr = array();
    $sortedobj = new stdClass();

    if (is_object($obj)) {
        foreach ($obj as $key => $value) {
            $arr[$key] = sort_obj($value);
        }
        ksort($arr);
        foreach ($arr as $key => $value) {
            $sortedobj->$key = $value;
        }
    } else if (is_array($obj)) {
        // TODO klären ob Arrays auch sortiert werden sollten.
        $sortedobj = $obj;
    } else if (is_string($obj)) {
        $sortedobj = $obj;
    }
    return $sortedobj;
}

/**
 * Builds an html version of a certificate according to a predefined template.
 *
 * @param int $id Course_module id of an ilddigitalcert instance. Needed to get the right template.
 * @param string $certmetadatajson Json string containing all the data that should be displayed.
 * @return string HTML certificate.
 */
function get_certificatehtml($id, $certmetadatajson) {
    global $DB;
    $html = $DB->get_field('ilddigitalcert', 'template', array('id' => $id));
    $matches = array();
    preg_match_all('~{(.+?)}~s', $html, $matches);
    foreach ($matches[1] as $match) {
        $elements = explode('|', $match);
        $jsonobj = json_decode($certmetadatajson);
        foreach ($elements as $element) {
            foreach ($jsonobj as $name => $value) {
                if ($name == $element) {
                    if (is_array($value)) {
                        $jsonobj = '<ul>';
                        foreach ($value as $val) {
                            $jsonobj .= '<li>' . $val . '</li>';
                        }
                        $jsonobj .= '</ul>';
                    } else {
                        if ($name == 'issuedOn') {
                            $value = date('d.m.Y', strtotime($value));
                        }
                        $jsonobj = $value;
                    }
                    break;
                }
            }
        }
        try {
            if (is_string($jsonobj)) {
                $html = str_replace('{'.$match.'}', $jsonobj, $html);
            }
        } catch (Exception $e) {
            throw new \moodle_exception('could_not_replace_string', 'mod_ilddigitalcert');
        }
    }

    return $html;
}

/**
 * Retrieve an issued certificates metadata for a given user and course module.
 *
 * @param int $userid
 * @param int $cmid
 * @param int $ueid
 * @return string|boolean Returns the metadata as json string or false the certifiacte doesn't exist.
 */
function get_issued_certificate($userid, $cmid, $ueid) {
    global $DB;
    if ($issued = $DB->get_record('ilddigitalcert_issued', array('userid' => $userid, 'cmid' => $cmid, 'enrolmentid' => $ueid))) {
        return $issued->metadata;
    }
    return false;
}

/**
 * Check if there is an issued certificate for a given user and course module. If so, return true, else false.
 *
 * @param int $userid
 * @param int $cmid
 * @param int $ueid
 * @return boolean
 */
function is_issued($userid, $cmid, $ueid) {
    global $DB;
    if ($DB->record_exists('ilddigitalcert_issued', array('userid' => $userid, 'cmid' => $cmid, 'enrolmentid' => $ueid))) {
        return true;
    }
    return false;
}

/**
 * Reissue a certifiacte.
 *
 * @param certificate $certificate
 * @param stdClass $cm Course module.
 * @return boolean True if reissuance was successful.
 */
function reissue_certificate($certificate, $cm) {
    global $DB;

    $recipientid = $certificate->get_subjectid();
    $courseid = $DB->get_field('course_modules', 'course', array('id' => $cm->id));
    $context = context_module::instance($cm->id);

    // Get enrolmentid.
    $sql = 'SELECT ue.id FROM {user_enrolments} ue, {enrol} e
             WHERE ue.enrolid = e.id
               and e.courseid = :courseid
               and ue.userid = :userid ';
    $params = array('courseid' => $courseid, 'userid' => $recipientid);
    $enrolmentid = 0;
    if ($enrolment = $DB->get_records_sql($sql, $params)) {
        if (count($enrolment) > 1) {
            throw new \moodle_exception(
                'to_many_enrolments',
                'mod_ilddigitalcert',
                new moodle_url(
                    '/mod/ilddigitalcert/course/view.php',
                    array('id' => $courseid)
                )
            );
        } else {
            foreach ($enrolment as $em) {
                $enrolmentid = $em->id;
            }
        }
    } else {
        throw new \moodle_exception(
            'not_enrolled',
            'mod_ilddigitalcert',
            new moodle_url(
                '/mod/ilddigitalcert/course/view.php',
                array('id' => $courseid)
            )
        );
    }

    // Get current certificate record.
    $issued = $DB->get_record(
        'ilddigitalcert_issued',
        array('userid' => $recipientid, 'cmid' => $cm->id, 'enrolmentid' => $enrolmentid),
        '*',
        MUST_EXIST
    );
    if (!$issued) {
        throw new \coding_exception(
            'certificate_not_found',
            'mod_ilddigitalcert',
            new moodle_url('/mod/ilddigitalcert/course/view.php', array('id' => $courseid))
        );
    }

    // Check if cert is already in blockchain. If so, throw error.
    if (isset($issued->txhash)) {
        throw new \moodle_exception(
            'already_in_blockchain',
            'mod_ilddigitalcert',
            new moodle_url(
                '/mod/ilddigitalcert/course/view.php',
                array('id' => $courseid)
            )
        );
    }

    $issued->name = $certificate->get_title();
    $issued->timemodified = time();

    // Reissue.
    $certificate->issue($cm, $issued->id, $issued->timemodified);

    $issued->metadata = $certificate->get_ob();
    $issued->edci = $certificate->get_edci();

    // Update db record.
    $DB->update_record('ilddigitalcert_issued', $issued);

    // Log certificate_reissued event.
    $event = \mod_ilddigitalcert\event\certificate_reissued::create(
        array('context' => $context, 'objectid' => $issued->id, 'relateduserid' => $issued->userid)
    );
    $event->trigger();

    return true;
}

/**
 * Issues a new digital certificate.
 *
 * @param certificate $certificate
 * @param stdClass $cm Course module.
 * @return string Returns the metadata of the issued certificate as a json string.
 */
function issue_certificate($certificate, $cm) {
    global $DB, $CFG, $SITE;

    $recipient = $DB->get_record('user', array('id' => $certificate->get_subjectid()));
    $courseid = $DB->get_field('course_modules', 'course', array('id' => $cm->id));
    $context = context_module::instance($cm->id);

    // Get enrolmentid.
    $sql = 'SELECT ue.id FROM {user_enrolments} ue, {enrol} e
             WHERE ue.enrolid = e.id
               and e.courseid = :courseid
               and ue.userid = :userid ';
    $params = array('courseid' => $courseid, 'userid' => $recipient->id);

    $enrolmentid = 0;
    if ($enrolment = $DB->get_records_sql($sql, $params)) {
        if (count($enrolment) > 1) {
            throw new moodle_exception(
                'to_many_enrolments',
                'mod_ilddigitalcert',
                new moodle_url('/mod/ilddigitalcert/course/view.php', array('id' => $courseid))
            );
        } else {
            foreach ($enrolment as $em) {
                $enrolmentid = $em->id;
            }
        }
    } else {
        throw new moodle_exception(
            'not_enrolled',
            'mod_ilddigitalcert',
            new moodle_url('/mod/ilddigitalcert/course/view.php', array('id' => $courseid))
        );
    }
    if ($issued = $DB->get_record(
        'ilddigitalcert_issued',
        array('userid' => $recipient->id, 'cmid' => $cm->id, 'enrolmentid' => $enrolmentid)
    )) {
        return $issued->metadata;
    }

    // Set new db record data.
    $issued = new stdClass();
    $issued->userid = $recipient->id;
    $issued->cmid = $cm->id;
    $issued->courseid = $courseid;
    $issued->name = $certificate->get_title();
    $issued->inblockchain = false;
    $issued->timecreated = time();
    $issued->timemodified = time();
    $issued->metadata = '';
    $issued->enrolmentid = $enrolmentid;

    $issuedid = $DB->insert_record('ilddigitalcert_issued', $issued);
    $issued->id = $issuedid;

    // Update the metadata certificate.
    $certificate->issue($cm, $issued->id, $issued->timemodified);

    $issued->metadata = $certificate->get_ob();
    $issued->edci = $certificate->get_edci();

    // Update record.
    $DB->update_record('ilddigitalcert_issued', $issued);

    // Log certificate_issued event.
    $event = \mod_ilddigitalcert\event\certificate_issued::create(
        array('context' => $context, 'objectid' => $issued->id, 'relateduserid' => $issued->userid)
    );
    $event->trigger();

    // Get ilddigitalcert settings.
    $certsettingssql = "SELECT cert.automation, cert.auto_certifier, cert.auto_pk
                          FROM {course_modules} cm
                          JOIN {ilddigitalcert} cert
                            ON cm.instance = cert.id
                         WHERE cm.id = :cmid;";
    $certsettings = $DB->get_record_sql($certsettingssql, array('cmid' => $cm->id), IGNORE_MISSING);

    // If automation is enabled, issued certificate will be signed and written
    // to the blockchain using the pk of the selected certifier.
    if ($certsettings->automation && $certsettings->auto_certifier && $certsettings->auto_pk) {
        if ($certifier = $DB->get_record('user', array('id' => $certsettings->auto_certifier), '*', IGNORE_MISSING)) {
            if ($pk = \mod_ilddigitalcert\crypto_manager::decrypt($certsettings->auto_pk)) {
                if (certificate_manager::to_blockchain($issued, $certifier, $pk)) {
                    return $issued->metadata;
                }
            }
        }
    }

    // Email to user, if it has to be signed and written to the blockchain still.
    $fromuser = core_user::get_support_user();
    $fullname = explode(' ', get_string('modulenameplural', 'mod_ilddigitalcert'));
    $fromuser->firstname = $fullname[0];
    $fromuser->lastname = $fullname[1];
    $subject = get_string('subject_new_certificate', 'mod_ilddigitalcert');
    $a = new stdClass();
    $a->fullname = $recipient->firstname . ' ' . $recipient->lastname;
    $a->url = $CFG->wwwroot . '/mod/ilddigitalcert/view.php?id=' . $cm->id;
    $a->from = $SITE->fullname;
    $messagehtml = get_string('message_new_certificate_html', 'mod_ilddigitalcert', $a);
    $message = html_to_text($messagehtml);
    email_to_user($recipient, $fromuser, $subject, $message, $messagehtml);

    return $issued->metadata;
}

/**
 * Calculates the expiration date and returns a string in iso time format.
 *
 * @param int $expiredate
 * @param int $expireperiod
 * @return string|null Returns the iso datetime of the expiration. Null if no expiration is set.
 */
function get_expiredate($expiredate, $expireperiod) {
    if ($expiredate <= 0) {
        if ($expireperiod <= 0) {
            return null;
        }
        $expiredate = time() + $expireperiod;
    }
    return date('c', $expiredate);
}

/**
 * Retrieves a modified record of an ilddigitalcert course module.
 * It is modified in a way that expertise are stored as an array instead of as a single string.
 * And tags that belonging to the corresponding coursemodule are also added to the return stdClass.
 *
 * @param stdClass $cm Course module.
 * @return stdClass
 */
function get_digitalcert($cm) {
    global $DB;
    $digitalcert = $DB->get_record('ilddigitalcert', array('id' => $cm->instance), '*', MUST_EXIST);
    // Expertise.
    $lines = preg_split("/[\r\n]+/", $digitalcert->expertise);
    $digitalcert->expertise = $lines;
    // Tags.
    $sql = 'select t.rawname
              from mdl_tag_instance ti, mdl_tag t
             where ti.itemtype = "course_modules"
               and ti.itemid = :id
               and t.id = ti.tagid';
    $params = array('id' => $cm->id);
    $records = $DB->get_records_sql($sql, $params);
    $tags = array();
    foreach ($records as $record) {
        $tags[] = $record->rawname;
    }
    $digitalcert->tags = $tags;

    return $digitalcert;
}

/**
 * Encodes the given $institutionprfile string with base58.
 *
 * @param string $institutionprofile
 * @return string Encoded $institutionprofile.
 */
function get_ipfs_hash($institutionprofile) {
    global $CFG;
    $institutionprofile = '1220' . substr($institutionprofile, 2);
    // TODO path to perl into settings.
    $file = '/usr/bin/perl ' . $CFG->dirroot . '/mod/ilddigitalcert/perl/base58_encode.pl ' . $institutionprofile;
    ob_start();
    passthru($file);
    $ipfshash = ob_get_contents();
    ob_end_clean();
    return $ipfshash;
}

/**
 * Retrieves the institution record stored in the Interplanetary File system identified by the given $ipfshash.
 *
 * @param string $ipfshash
 * @return stdClass
 */
function get_institution($ipfshash) {
    $institution = new stdClass();

    $ipfsurl = 'https://ipfs.io/ipfs/' . $ipfshash;

    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ipfsurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout in seconds.

        $jsonresult = curl_exec($ch);
        curl_close($ch);
    } catch (Exception $e) {
        throw new moodle_exception('Failed retrieving institution data drom IPFS.');
    }

    $institution = json_decode($jsonresult);

    return $institution;
}

/**
 * Registers a user as a certifier in the the blockchain.
 * If the process is successful the user preferences of the user are updated
 * to contain their blockchain address that identifies them as a certifier.
 *
 * @param int $userid
 * @param string $useraddress
 * @param string $adminpk
 * @return boolean True if the certifier was added sucessfully, else false.
 */
function add_certifier($userid, $useraddress, $adminpk) {
    global $DB;
    // Check if user already exists in user_preferences.
    if ($userpref = $DB->get_record('user_preferences', array('name' => 'mod_ilddigitalcert_certifier', 'userid' => $userid))) {
        if (strpos($userpref->value, 'not_registered_pk') === false) {
            throw new moodle_exception(
                'user_is_already_certifier',
                'mod_ilddigitalcert',
                new moodle_url('/mod/ilddigitalcert/edit_certifiers.php')
            );
        }
    }
    // Check if $useraddress already exists in user_preferences.
    if ($userpref = $DB->get_record('user_preferences', array('name' => 'mod_ilddigitalcert_certifier', 'value' => $useraddress))) {
        throw new moodle_exception(
            'address_is_already_used',
            'mod_ilddigitalcert',
            new moodle_url('/mod/ilddigitalcert/edit_certifiers.php')
        );
    }
    // Add to blockchain.
    if (web3_manager::add_certifier_to_blockchain($useraddress, $adminpk)) {
        // If added certifier successfully, add userpref.
        set_user_preference('mod_ilddigitalcert_certifier', $useraddress, $userid);
        return true;
    } else {
        throw new moodle_exception(
            'error_while_adding_certifier_to_blockchain',
            'mod_ilddigitalcert',
            new moodle_url('/mod/ilddigitalcert/edit_certifiers.php')
        );
    }
    return false;
}

/**
 * Unregisters a user as a certifier in the blockchain and
 * unsets the user preferences entry that identifies the user as a certifier.
 *
 * @param int $userprefid
 * @param string $adminpk
 * @return boolean True if the certifier was removed sucessfully, else false.
 */
function remove_certifier($userprefid, $adminpk) {
    global $DB;
    if (!$userpref = $DB->get_record('user_preferences', array('id' => $userprefid))) {
        // Not a certifier.
        throw new moodle_exception(
            'certifier_already_removed_from_blockchain',
            'mod_ilddigitalcert',
            new moodle_url('/mod/ilddigitalcert/edit_certifiers.php')
        );
    }

    $useraddress = $userpref->value;

    if (!web3_manager::is_accredited_certifier($useraddress)) {
        // Certifier already removed.
        unset_user_preference('mod_ilddigitalcert_certifier', $userpref->userid);
        throw new moodle_exception(
            'certifier_already_removed_from_blockchain',
            'mod_ilddigitalcert',
            new moodle_url('/mod/ilddigitalcert/edit_certifiers.php')
        );
    }

    // Remove certifier from blockchain.
    if (!web3_manager::remove_certifier_from_blockchain($useraddress, $adminpk)) {
        throw new coding_exception(
            'error_while_removing_certifier_from_blockchain',
            'mod_ilddigitalcert',
            new moodle_url('/mod/ilddigitalcert/edit_certifiers.php')
        );
    }

    // If success, delete from userpref.
    unset_user_preference('mod_ilddigitalcert_certifier', $userpref->userid);
    // TODO Email to ex-certifier.
    return true;
}

/**
 * Checks wether an ilddigitalcert_issuer record exists, that contains a blockchain address.
 *
 * @param string $address
 * @return boolean True if an issuer record containing an address exists, else false.
 */
function institution_address_exists($address) {
    global $DB;
    if ($DB->get_record('ilddigitalcert_issuer', array('address' => $address))) {
        return true;
    }
    return false;
}

/**
 * Retrieves the corresponding name of an ilddigitalcert_issuer record, that is identified by its address.
 *
 * @param string $institutionaddress
 * @return string
 * @throws moodle_exception Throws an exception if no record with the given address was found.
 */
function get_issuer_name_from_address($institutionaddress) {
    global $DB;
    if ($issuers = $DB->get_records('ilddigitalcert_issuer', array('address' => $institutionaddress))) {
        if (count($issuers) > 1) {
            throw new moodle_exception('found_address_more_than_one_times', 'mod_ilddigitalcert');
        }
        foreach ($issuers as $issuer) {
            return $issuer->name;
        }
    }
    throw new moodle_exception('address_not_found', 'mod_ilddigitalcert');
}

/**
 * Reset the completion records of a user in a given course,
 * as well as the their scorm track and quiz attemot records.
 *
 * @param int $courseid
 * @param int $userid
 * @return void
 */
function reset_user($courseid, $userid) {
    global $DB;

    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

    $completion = new completion_info($course);
    if (!$completion->is_enabled()) {
        return;
    }

    $DB->delete_records_select(
        'course_modules_completion',
        'coursemoduleid IN (SELECT id
            FROM mdl_course_modules
            WHERE course=?)
            AND userid=?',
        array($courseid, $userid)
    );
    $DB->delete_records('course_completions', array('course' => $courseid, 'userid' => $userid));
    $DB->delete_records('course_completion_crit_compl', array('course' => $courseid, 'userid' => $userid));

    $dbman = $DB->get_manager();

    if ($dbman->table_exists('scorm_scoes_track')) {
        $DB->delete_records_select(
            'scorm_scoes_track',
            'scormid IN (SELECT id FROM mdl_scorm WHERE course=?)
                AND userid=?',
            array($courseid, $userid)
        );
    }

    if ($dbman->table_exists('quiz')) {
        $attempts = $DB->get_records_sql_menu("
            SELECT id, uniqueid
              FROM {quiz_attempts}
             WHERE userid = $userid
               AND quiz IN (SELECT id
                              FROM mdl_quiz
                             WHERE course = $courseid) ");
        if ($attempts) {
            foreach ($attempts as $attemptid => $usageid) {
                question_engine::delete_questions_usage_by_activity($usageid);
                $DB->delete_records('quiz_attempts', array('id' => $attemptid));
            }
        }
    }
    cache::make('core', 'completion')->purge();
}

/**
 * Echos html formatted to contain the given $metadata.
 *
 * @param stdClass|array $metadata
 * @return void
 */
function display_metadata($metadata) {
    if (is_array($metadata)) {
        echo '<ul>';
        foreach ($metadata as $value) {
            echo '<li>';
            echo $value;
            echo '</li>';
        }
        echo '</ul>';
    } else if (is_object($metadata)) {
        echo '<ul>';
        foreach ($metadata as $key => $value) {
            if (
                $key != 'abi' and $key != '@context'
                and $key != 'type' and $value != ''
                and $key != 'extensions:assertionpageB4E'
            ) {
                if ($key == 'image') {
                    echo '<br />';
                    echo '<img src="' . $value . '" style="max-width:150px; max-height:150px;">';
                } else {
                    if ($key == 'issuedOn' or $key == 'date' or $key == 'expires' or $key == 'certificationdate') {
                        $value = date('d.m.Y', strtotime($value));
                    }
                    if ($key == 'startdate') {
                        $value = date('d.m.Y', strtotime($value));
                    }
                    if ($key == 'enddate') {
                        $value = date('d.m.Y', strtotime($value));
                    }
                    if (has_content($value)) {
                        echo '<li>';
                        echo '<b>' . $key . '</b>: ';
                        display_metadata($value);
                        echo '</li>';
                    }
                }
            }
        }
        echo '</ul>';
    } else {
        echo $metadata;
    }
}

/**
 * Checks wether the given parameter is either a non empty string or an object with non empty values.
 *
 * @param string|\stdClass|array $metadataobj
 * @return boolean True if $metadataobj has content, else false.
 */
function has_content($metadataobj) {
    if (is_string($metadataobj) and $metadataobj != '') {
        return true;
    }
    foreach ($metadataobj as $key => $value) {
        if ($key != '@context' and $key != 'type' and $value != '' and $key != 'extensions:assertionpageB4E') {
            return true;
        }
    }
    return false;
}

/**
 * Sends a file identified by its id to the current user's browser.
 *
 * @param int $fileid
 * @return void
 */
function download_file($fileid) {
    global $DB;

    if ($file = $DB->get_record('files', array('id' => $fileid))) {

        $filestorage = get_file_storage();

        $storedfile = $filestorage->get_file(
            $file->contextid,
            $file->component,
            $file->filearea,
            $file->itemid,
            $file->filepath,
            $file->filename
        );

        send_stored_file($storedfile, null, 0, false);
    }
}

/**
 * Gets all the registered certifiers and if $course is set only those
 * that are also enroled in the specified course.
 *
 * @param int $course Id of a course.
 * @return array Moodle users that are registered certifiers.
 */
function get_certifiers($course = false) {
    global $DB;

    $certifiers = $DB->get_records('user_preferences', array('name' => 'mod_ilddigitalcert_certifier'));

    if (empty($certifiers)) {
        return null;
    }

    $certifierids = [];
    foreach ($certifiers as $certifier) {
        $certifierids[] = $certifier->userid;
    }

    if (!$course) {
        return $certifierids;
    }

    list($insql, $inparams) = $DB->get_in_or_equal($certifierids, SQL_PARAMS_NAMED, 'ctx');
    $sql = "SELECT u.*
        FROM mdl_role_assignments ra
        JOIN mdl_user u ON u.id = ra.userid
        JOIN mdl_role r ON r.id = ra.roleid
        JOIN mdl_context cxt ON cxt.id = ra.contextid
        JOIN mdl_course c ON c.id = cxt.instanceid
        WHERE ra.contextid = cxt.id
        AND cxt.contextlevel = 50
        AND cxt.instanceid = c.id
        AND roleid < 5
        AND c.id = :course";
    $conditions = array('course' => $course);
    if (!empty($certifierids)) {
        $sql .= " AND u.id $insql";
        $conditions = array_merge($conditions, $inparams);
    }
    $records = $DB->get_records_sql($sql, $conditions);
    return $records;
}

/**
 * Creates a filename for a given certificate.
 *
 * @param stdClass $certificaterecord
 * @param certificate $metacertificate
 * @return string
 */
function get_certificate_name($certificaterecord, $metacertificate) {
    $certificatename = str_replace(array(' ', '(', ')'), '_', $certificaterecord->name);
    return $certificatename . '_' .
        $metacertificate->get_credentialsubject()->get_givennames() . '_' .
        $metacertificate->get_credentialsubject()->get_familyname() . '_' .
        strtotime($metacertificate->get_issuedon()
    );
}

/**
 * Creates .bcrt and .xml files containing the OpenBadge and EDCI certificate metadata.
 *
 * @param stdClass $certificaterecord
 * @param certificate $metacertificate
 * @return void
 */
function create_certificate_files($certificaterecord, $metacertificate) {
    $modulecontext = context_module::instance($certificaterecord->cmid);
    $fs = get_file_storage();

    $filename = get_certificate_name($certificaterecord, $metacertificate);

    // Create .bcrt file.
    $fileinfo = array(
        'contextid' => $modulecontext->id,
        'component' => 'mod_ilddigitalcert',
        'filearea' => 'metadata',
        'itemid' => $certificaterecord->id,
        'filepath' => '/',
        'filename' => $filename . '.bcrt'
    );
    $file = $fs->get_file(
        $fileinfo['contextid'],
        $fileinfo['component'],
        $fileinfo['filearea'],
        $fileinfo['itemid'],
        $fileinfo['filepath'],
        $fileinfo['filename']
    );
    if ($file) {
        $file->delete();
    }

    // Institution token / salt hinzufügen damit der Hash auch richtig berechnet werden kann.
    $token = get_token($certificaterecord->institution_token);
    $metacertificate->add_institutiontoken($token);
    $certificaterecord->metadata = $metacertificate->get_ob();

    $fs->create_file_from_string($fileinfo, $certificaterecord->metadata);

    if (isset($certificaterecord->edci)) {
        // Create .xml file.
        $fileinfoxml = array(
            'contextid' => $modulecontext->id,
            'component' => 'mod_ilddigitalcert',
            'filearea' => 'metadata',
            'itemid' => $certificaterecord->id,
            'filepath' => '/',
            'filename' => $filename . '.xml'
        );
        $file = $fs->get_file(
            $fileinfoxml['contextid'],
            $fileinfoxml['component'],
            $fileinfoxml['filearea'],
            $fileinfoxml['itemid'],
            $fileinfoxml['filepath'],
            $fileinfoxml['filename']
        );
        if ($file) {
            $file->delete();
        }

        // Add institution token to edci.
        $certificaterecord->edci = $metacertificate->get_edci();
        // Create .xml file.
        $fs->create_file_from_string($fileinfoxml, $certificaterecord->edci);
    }
}

/**
 * Retrieves the .bcrt and .xml files containing the OpenBadge and EDCI certificate metadata from the filesystem.
 *
 * @param stdClass $certificaterecord
 * @param certificate $metacertificate
 * @return array
 */
function get_certificate_files($certificaterecord, $metacertificate) {
    $files = array();
    $modulecontext = context_module::instance($certificaterecord->cmid);
    $fs = get_file_storage();

    $filename = get_certificate_name($certificaterecord, $metacertificate);

    // Create .bcrt file.
    $fileinfo = array(
        'contextid' => $modulecontext->id,
        'component' => 'mod_ilddigitalcert',
        'filearea' => 'metadata',
        'itemid' => $certificaterecord->id,
        'filepath' => '/',
        'filename' => $filename . '.bcrt'
    );
    $bcrtfile = $fs->get_file(
        $fileinfo['contextid'],
        $fileinfo['component'],
        $fileinfo['filearea'],
        $fileinfo['itemid'],
        $fileinfo['filepath'],
        $fileinfo['filename']
    );
    $files[] = $bcrtfile;

    // Get .xml file.
    $fileinfoxml = array(
        'contextid' => $modulecontext->id,
        'component' => 'mod_ilddigitalcert',
        'filearea' => 'metadata',
        'itemid' => $certificaterecord->id,
        'filepath' => '/',
        'filename' => $filename . '.xml'
    );
    $xmlfile = $fs->get_file(
        $fileinfoxml['contextid'],
        $fileinfoxml['component'],
        $fileinfoxml['filearea'],
        $fileinfoxml['itemid'],
        $fileinfoxml['filepath'],
        $fileinfoxml['filename']
    );
    $files[] = $xmlfile;

    return $files;
}
