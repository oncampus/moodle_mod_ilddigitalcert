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

require_once('schema.php');

/**
 * Initiates and controls the download of the certificate in the requested format.
 * The certificate can be downloaded as .bcrt file in obenBadge format, .xml file in edci format or as a pdf.
 *
 * @param int $modulecontextid needed to locate the srored certificates in file storage.
 * @param string $icid itemid usually corresponding row id of database table.
 * @param string $download Controls what kind of file gets sent to the user. Expected values are 'json', 'edci' and 'pdf'.
 */
function download_json($modulecontextid, $icid, $download) {
    global $DB, $CFG;

    $fs = get_file_storage();
    // Retrieves openBadge cert from file storage.
    $storedfile = $fs->get_file($modulecontextid,
                                'mod_ilddigitalcert',
                                'metadata',
                                $icid,
                                '/',
                                'certificate.bcrt');
    // Retrieves edci cert from file storage.
    $storededci = $fs->get_file($modulecontextid,
                                'mod_ilddigitalcert',
                                'metadata',
                                $icid,
                                '/',
                                'certificate.xml');

    if ($download == 'json') {
        send_stored_file($storedfile, null, 0, true);
    } else if ($download == 'edci') {
        send_stored_file($storededci, null, 0, true);
    } else if ($download == 'pdf') {
        $hash = '';
        $filename = 'certificate';
        // Retrieve issued certificate from database.
        if ($issuedcertificate = $DB->get_record('ilddigitalcert_issued', array('id' => $icid))) {

            $metadatajson = $issuedcertificate->metadata;

            // Add salt to openBadge cert.
            $salt = get_token($issuedcertificate->institution_token);
            $metadata = json_decode($metadatajson);
            $metadata->{'extensions:institutionTokenILD'} = get_extension_institutiontoken_ild($salt);
            $metadatajson = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if(isset($issuedcertificate->edci)) {
                // Add salt to edci.
                $bcert = mod_ilddigitalcert\bcert\certificate::from_edci($issuedcertificate->edci);
                $bcert->add_institution_token($salt);
                $issuedcertificate->edci = $bcert->get_edci();
            }

            // Now that the salt is added a hash can be created.
            $hash = calculate_hash($metadatajson);

            $certificatename = str_replace(
                array(
                    ' ',
                    '(',
                    ')'
                ),
                '_',
                $issuedcertificate->name
            );
            $filename = $certificatename . '_' .
                $metadata->{'extensions:recipientB4E'}->givenname . '_' .
                $metadata->{'extensions:recipientB4E'}->surname . '_' .
                strtotime($metadata->issuedOn);
        }
        $content = $storedfile->get_content();

        require_once(__DIR__ . '/vendor/autoload.php');

        $certificate = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'margin_top' => 0,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_bottom' => 0,
            'format' => [210, 297]
        ]);
        $certificate->showImageErrors = true;

        // Decode the assertionpage info included in the ob certificate and write it as html to the pdf
        $html = '<h1>Error</h1>';

        $json = $content;
        if (isset($json) and $json != '') {
            $jsonobj = json_decode($json);
            $html = base64_decode($jsonobj->{'extensions:assertionpageB4E'}->assertionpage);
        }

        $certificate->WriteHTML($html);

        // Generate pdf footer section including the hash value of the ob certificate.
        $certificate->WriteHTML(get_pdf_footerhtml($hash));

        $fileid = $storedfile->get_id(); // Get fileid.

        // add openBadge and edci files as attachements to the pdf
        $associatedFiles = [
            [
            'name' => $filename.'.bcrt',
            'mime' => 'application/json',
            'description' => 'some description',
            'AFRelationship' => 'Alternative',
            'path' => $CFG->wwwroot.'/mod/ilddigitalcert/download_pdf.php?id='.$fileid
            ]
        ];
    
        if($storededci) {
            array_push($associatedFiles, [
                'name' => $filename.'.xml',
                'mime' => 'application/xml',
                'description' => 'some description',
                'AFRelationship' => 'Alternative',
                'path' => $CFG->wwwroot.'/mod/ilddigitalcert/download_pdf.php?id='.$storededci->get_id()
            ]);
        }
    
        $certificate->SetAssociatedFiles($associatedFiles);

        // Start download of the pdf file.
        $certificate->Output($filename.'.pdf', 'I');

        return;
    }
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
 * Stores a hash of an openBadge certificate in the clockchain.
 * Before calculating the hash the signature and institution token has to be added to the certificate.
 *
 * @param object $issuedcertificate Object that contains the certificate that needs to be stored in the bc.
 * @param core_user $fromuser Moodle user that signs the certificate.
 * @param string $pk private key of the certifier.
 * @return bool Returns false if the cert couldn´t be written to the blockchain.
 */
function to_blockchain($issuedcertificate, $fromuser, $pk) {
    global $DB, $CFG, $SITE;

    require_once('web3lib.php');
    $pref = get_user_preferences('mod_ilddigitalcert_certifier', false, $fromuser);
    if (!$pref) {
        print_error('not_a_certifier', 'mod_ilddigitalcert');
    } else {
        if ($pref != get_address_from_pk($pk)) {
            print_error('wrong_private_key', 'mod_ilddigitalcert');
        }
    }

    if (isset($issuedcertificate->txhash)) {
        return false;
    }
    // Add signature.
    $issuedcertificate = add_signature($issuedcertificate, $fromuser);
    $metadata = $issuedcertificate->metadata;

    // Save salt/token to file.
    if (!$tokenid = save_token()) {
        $tokenid = 'error';
    }
    $salt = get_token($tokenid);
    $metadata = json_decode($metadata);
    $metadata->{'extensions:institutionTokenILD'} = get_extension_institutiontoken_ild($salt);
    // Contract parameter.
    $metadata->{'extensions:contractB4E'} = get_extension_contract_b4e();

    $metadata = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    $hash = calculate_hash($metadata);
    // Delete institutionToken. Only added for download pdf or json.
    $metadata = json_decode($metadata);
    unset($metadata->{'extensions:institutionTokenILD'});
    $metadata = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    $startdate = strtotime(json_decode($metadata)->issuedOn);
    if (isset(json_decode($metadata)->expires)) {
        $enddate = strtotime(json_decode($metadata)->expires);
    } else {
        $enddate = 9999999999;//0; TODO Settings (if demo: 9999999999, if prod: 0)
    }
    if ($enddate != 0 and $enddate <= $startdate) {
        return false; // TODO show Errormessage
    }
    $hashes = save_hash_in_blockchain($hash, $startdate, $enddate, $pk);
    if (isset($hashes->txhash)) {
        // Add verification.
        $metadata = json_decode($metadata);
        $metadata->verification = new stdClass();
        $metadata->verification->{'extensions:verifyB4E'} = get_extension_verify_b4e($hash);

        $json = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        // Save hashes in db issued.
        $issuedcertificate->inblockchain = true;
        $issuedcertificate->certhash = $hashes->certhash;
        $issuedcertificate->txhash = $hashes->txhash;
        $issuedcertificate->metadata = $json;
        $issuedcertificate->institution_token = $tokenid;

        
        // Create edci-Certificate.
        // Convert openBadge metadata to edci.
        $edci = \mod_ilddigitalcert\bcert\certificate::from_ob($json)->get_edci();
        // Add edci to $issuedcertificate.
        $issuedcertificate->edci = $edci;

        $DB->update_record('ilddigitalcert_issued', $issuedcertificate);

        if ($receiver = $DB->get_record('user', array('id' => $issuedcertificate->userid))) {
            // Email to user.
            $fromuser = core_user::get_support_user();
            $fullname = explode(' ', get_string('modulenameplural', 'mod_ilddigitalcert'));
            $fromuser->firstname = $fullname[0];
            $fromuser->lastname = $fullname[1];
            $subject = get_string('subject_new_digital_certificate', 'mod_ilddigitalcert');
            $a = new stdClass();
            $a->fullname = $receiver->firstname . ' ' . $receiver->lastname;
            $a->url = $CFG->wwwroot . '/mod/ilddigitalcert/view.php?id=' . $issuedcertificate->cmid;
            $a->from = $SITE->fullname;
            $message = get_string('message_new_digital_certificate', 'mod_ilddigitalcert', $a);
            $messagehtml = get_string('message_new_digital_certificate_html', 'mod_ilddigitalcert', $a);
            email_to_user($receiver, $fromuser, $subject, $message, $messagehtml);
        }
        return true;
    }
    return false;
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
 * Signs a certificate.
 *
 * @param object $issuedcertificate An unsigned cerificate.
 * @param core_user $fromuser The user that gets to sign the certificate
 * @return object Returns the signed signature.
 */
function add_signature($issuedcertificate, $fromuser) {
    global $contexturl;

    $metadata = $issuedcertificate->metadata;
    $metadataobj = json_decode($metadata);

    // Create signature extension.
    $extension = new stdClass();
    // Get fromuser blockchain adress from userpreferences.
    $extension->address = get_user_preferences('mod_ilddigitalcert_certifier', false, $fromuser);
    $extension->email = $fromuser->email;
    $extension->surname = $fromuser->lastname;
    $extension->{'@context'} = $contexturl->signatureB4E;
    $extension->role = 'Trainer/in'; // TODO get role in this course.
    $extension->certificationdate = date('c', time());
    $extension->type = array('Extension', 'SignatureB4E');
    $extension->givenname = $fromuser->firstname;
    if (isset($fromuser->city) and $fromuser->city != '') {
        $extension->certificationplace = $fromuser->city; // TODO Is this correct?
    }

    $metadataobj->{'extensions:signatureB4E'} = $extension;
    $metadata = json_encode($metadataobj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    // Add signature to certificate.
    $issuedcertificate->metadata = $metadata;

    return $issuedcertificate;
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
 * Writes a hash to the blockchain.
 *
 * @param string $hash Hashed certificate.
 * @param int $startdate Start of certificate validity.
 * @param int $enddate End of certificate validity.
 * @param string $pk Private key of the certifier.
 * @return string|bool Returns a hash or false, if the hash couldn't be stored.
 */
function save_hash_in_blockchain($hash, $startdate, $enddate, $pk) {
    require_once('web3lib.php');

    $hashes = store_certificate($hash, $startdate, $enddate, $pk);

    if (isset($hashes->txhash)) {
        /*
        certificate hash:  $hashes->certhash
        startdate:         $startdate
        enddate:           $enddate
        ptx hash:          $hashes->txhash
        */
        return $hashes;
    }
    return false;
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
            print_error('could_not_replace_string',
                        'mod_ilddigitalcert');
        }
    }

    return $html;
}

function generate_certmetadata($cm, $user) {
    global $contexturl;
    $digitalcert = get_digitalcert($cm);

    $metadata = new stdClass();

    $metadata->badge = get_badge($cm, $digitalcert);
    if ($expiredate = get_expiredate($digitalcert->expiredate, $digitalcert->expireperiod)) {
        $metadata->expires = $expiredate;
    }
    $metadata->{'extensions:examinationRegulationsB4E'} = get_extension_examinationregulations_b4e($digitalcert);
    $metadata->{'@context'} = $contexturl->openbadges;
    $metadata->recipient = get_recipient();
    $metadata->{'extensions:recipientB4E'} = get_extension_recipient_b4e($user);
    $metadata->{'extensions:examinationB4E'} = get_extension_examination_b4e($digitalcert);
    $metadata->type = 'Assertion';

    return $metadata;
}

function get_issued_certificate($userid, $cmid, $ueid) {
    global $DB;
    if ($issued = $DB->get_record('ilddigitalcert_issued', array('userid' => $userid, 'cmid' => $cmid, 'enrolmentid' => $ueid))) {
        return $issued->metadata;
    }
    return false;
}

function is_issued($userid, $cmid, $ueid) {
    global $DB;
    if ($DB->record_exists('ilddigitalcert_issued', array('userid' => $userid, 'cmid' => $cmid, 'enrolmentid' => $ueid))) {
        return true;
    }
    return false;
}

function reissue_certificate($certmetadata, $userid, $cmid) {
    global $DB, $CFG;
    $courseid = $DB->get_field('course_modules', 'course', array('id' => $cmid));
    // Get enrolmentid.
    $sql = 'SELECT ue.id FROM {user_enrolments} ue, {enrol} e
             WHERE ue.enrolid = e.id
               and e.courseid = :courseid
               and ue.userid = :userid ';
    $params = array('courseid' => $courseid, 'userid' => $userid);
    $enrolmentid = 0;
    if ($enrolment = $DB->get_records_sql($sql, $params)) {
        if (count($enrolment) > 1) {
            print_error(
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
        print_error(
            'not_enrolled',
            'mod_ilddigitalcert',
            new moodle_url(
                '/mod/ilddigitalcert/course/view.php',
                array('id' => $courseid)
            )
        );
    }
    if ($issued = $DB->get_record(
        'ilddigitalcert_issued',
        array('userid' => $userid, 'cmid' => $cmid, 'enrolmentid' => $enrolmentid)
    )) {
        // Check if cert is already in blockchain. if so, print error.
        if (isset($issued->certhash)) {
            print_error(
                'already_in_blockchain',
                'mod_ilddigitalcert',
                new moodle_url(
                    '/mod/ilddigitalcert/course/view.php',
                    array('id' => $courseid)
                )
            );
        }

        $issued->name = $certmetadata->badge->name;
        $issued->timemodified = time();

        $certmetadata->id = $CFG->wwwroot . '/mod/ilddigitalcert/view.php?issuedid=' . $issued->id;
        $certmetadata->issuedOn = date('c', $issued->timemodified);
        $certmetadata->{'extensions:assertionreferenceB4E'} = get_extension_assertionreference_b4e($issued->id);

        // The assertionpageB4E contains the complete html, that is generated from the template and the complete metadata.
        $json = json_encode($certmetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $certmetadata->{'extensions:assertionpageB4E'} = get_extension_assertionpage_b4e($cmid, $json);
        // So json has to be generates again after this.
        $json = json_encode($certmetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $issued->metadata = $json;

        $DB->update_record('ilddigitalcert_issued', $issued);
        return true;
    } else {
        print_error(
            'certificate_not_found',
            'mod_ilddigitalcert',
            new moodle_url('/mod/ilddigitalcert/course/view.php', array('id' => $courseid))
        );
    }
}

function issue_certificate($certmetadata, $userid, $cmid) {
    global $DB, $CFG, $SITE;

    $courseid = $DB->get_field('course_modules', 'course', array('id' => $cmid));

    // Get enrolmentid.
    $sql = 'SELECT ue.id FROM {user_enrolments} ue, {enrol} e
             WHERE ue.enrolid = e.id
               and e.courseid = :courseid
               and ue.userid = :userid ';
    $params = array('courseid' => $courseid, 'userid' => $userid);

    $enrolmentid = 0;
    if ($enrolment = $DB->get_records_sql($sql, $params)) {
        if (count($enrolment) > 1) {
            print_error(
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
        print_error(
            'not_enrolled',
            'mod_ilddigitalcert',
            new moodle_url('/mod/ilddigitalcert/course/view.php', array('id' => $courseid))
        );
    }
    if ($issued = $DB->get_record(
        'ilddigitalcert_issued',
        array('userid' => $userid, 'cmid' => $cmid, 'enrolmentid' => $enrolmentid)
    )) {
        return $issued->metadata;
    }

    $issued = new stdClass();
    $issued->userid = $userid;
    $issued->cmid = $cmid;
    $issued->courseid = $courseid;
    $issued->name = $certmetadata->badge->name;
    $issued->inblockchain = false;
    $issued->timecreated = time();
    $issued->timemodified = time();
    $issued->metadata = '';
    $issued->enrolmentid = $enrolmentid;

    $issuedid = $DB->insert_record('ilddigitalcert_issued', $issued);
    $issued->id = $issuedid;

    $certmetadata->id = $CFG->wwwroot . '/mod/ilddigitalcert/view.php?issuedid=' . $issuedid;
    $certmetadata->issuedOn = date('c', $issued->timemodified);
    $certmetadata->{'extensions:assertionreferenceB4E'} = get_extension_assertionreference_b4e($issuedid);

    // The assertionpageB4E contains the complete html, that is generated from the template and the complete metadata.
    $json = json_encode($certmetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $certmetadata->{'extensions:assertionpageB4E'} = get_extension_assertionpage_b4e($cmid, $json);
    // So json has to be generates again after this.
    $json = json_encode($certmetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    $issued->metadata = $json;

    $DB->update_record('ilddigitalcert_issued', $issued);

    $cert_settings = $DB->get_record('ilddigitalcert', array('id' => $cmid), 'automation, auto_certifier, auto_pk', IGNORE_MISSING);

    // If automation is enabled, issued certificate will be signed and written
    // to the blockchain using the pk of the selected certifier.
    $in_blockchain = false;
    if ($cert_settings->automation && $cert_settings->auto_certifier && $cert_settings->auto_pk) {
        if ($certifier = $DB->get_record('user', array('id' => $cert_settings->auto_certifier), '*', IGNORE_MISSING)) {
            if ($pk = \mod_ilddigitalcert\crypto_manager::decrypt($cert_settings->auto_pk)) {
                $in_blockchain = to_blockchain($issued, $certifier, $pk);
            }
        }
    }

    // Email to user, if it has to be signed and written to the blockchain still.
    if (!$in_blockchain && $user = $DB->get_record('user', array('id' => $userid))) {
        $fromuser = core_user::get_support_user();
        $fullname = explode(' ', get_string('modulenameplural', 'mod_ilddigitalcert'));
        $fromuser->firstname = $fullname[0];
        $fromuser->lastname = $fullname[1];
        $subject = get_string('subject_new_certificate', 'mod_ilddigitalcert');
        $a = new stdClass();
        $a->fullname = $user->firstname . ' ' . $user->lastname;
        $a->url = $CFG->wwwroot . '/mod/ilddigitalcert/view.php?id=' . $cmid;
        $a->from = $SITE->fullname;
        $message = get_string('message_new_certificate', 'mod_ilddigitalcert', $a);
        $messagehtml = get_string('message_new_certificate_html', 'mod_ilddigitalcert', $a);
        email_to_user($user, $fromuser, $subject, $message, $messagehtml);
    }

    return $json;
}

function get_extension_assertionreference_b4e($issuedid) {
    // Unique reference for a certificate, given by certification authority.
    global $CFG, $contexturl;
    $extension = new stdClass();

    $extension->assertionreference = $CFG->wwwroot . '/mod/ilddigitalcert/view.php?issuedid=' . $issuedid;
    $extension->{'@context'} = $contexturl->assertionreferenceB4E;
    $extension->type = array('Extension', 'AssertionReferenceB4E');

    return $extension;
}

function get_extension_examination_b4e($digitalcert) {
    global $contexturl;
    $extension = new stdClass();

    $extension->{'@context'} = $contexturl->examinationB4E;
    $extension->type = array('Extension', 'ExaminationB4E');
    if ($digitalcert->examination_start > 0) {
        $extension->startdate = date('c', $digitalcert->examination_start);
    }
    if ($digitalcert->examination_end > 0) {
        $extension->enddate = date('c', $digitalcert->examination_end);
    }
    if ($digitalcert->examination_place != '') {
        $extension->place = $digitalcert->examination_place;
    }

    return $extension;
}

function get_extension_recipient_b4e($user) {
    global $contexturl;
    $extension = new stdClass();
    $userprofilefields = profile_user_record($user->id);

    if (isset($userprofilefields->birthdate) and $userprofilefields->birthdate != 0) {
        $extension->birthdate = date('c', $userprofilefields->birthdate);
    }
    $extension->reference = $user->id;
    $extension->email = $user->email;
    if (isset($userprofilefields->gender) and $userprofilefields->gender != '') {
        $extension->gender = $userprofilefields->gender;
    }
    if (isset($userprofilefields->birthplace) and $userprofilefields->birthplace != '') {
        $extension->birthplace = $userprofilefields->birthplace;
    }
    $extension->{'@context'} = $contexturl->recipientB4E;
    $extension->type = array('Extension', 'RecipientB4E');
    $extension->givenname = $user->firstname;
    if (isset($userprofilefields->birthname) and $userprofilefields->birthname != '') {
        $extension->birthname = $userprofilefields->birthname;
    }
    $extension->surname = $user->lastname;
    return $extension;
}

function get_recipient() {
    $recipient = new stdClass();
    $recipient->type = 'email';
    $recipient->hashed = false;
    return $recipient;
}

function get_extension_examinationregulations_b4e($digitalcert) {
    global $contexturl;
    $extension = new stdClass();
    $extension->title = $digitalcert->examination_regulations;
    $extension->regulationsid = $digitalcert->examination_regulations_id;
    $extension->url = $digitalcert->examination_regulations_url;
    $extension->{'@context'} = $contexturl->examinationRegulationsB4E;
    $extension->type = array('Extension', 'ExaminationRegulationsB4E');
    if ($digitalcert->examination_regulations_date != 0) {
        $extension->date = date('c', $digitalcert->examination_regulations_date);
    }
    return $extension;
}

function get_expiredate($expiredate, $expireperiod) {
    if ($expiredate == 0) {
        if ($expireperiod == 0) {
            return false;
        }
        $expiredate = time() + $expireperiod;
    }
    return date('c', $expiredate);
}

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

function get_extension_verify_b4e($hash) {
    global $CFG, $contexturl;
    $verification = new stdClass();
    // TODO get alternative url from settings.
    $verification->verifyaddress = $CFG->wwwroot . '/mod/ilddigitalcert/verify.php?hash=' . $hash;
    $verification->type = array('Extension', 'VerifyB4E');
    $verification->assertionhash = 'sha256$' . substr($hash, 2);
    $verification->{'@context'} = $contexturl->verifyB4E;
    return $verification;
}

function get_badge($cm, $digitalcert) {
    global $contexturl;
    $badge = new stdClass();

    $badge->description = $digitalcert->description;
    $badge->name = $digitalcert->name;
    $badge->{'extensions:badgeexpertiseB4E'} = get_extension_badgeexpertise_b4e($digitalcert->expertise);
    $badge->issuer = get_issuer($digitalcert->issuer);
    $badge->{'@context'} = $contexturl->openbadges;
    $badge->type = 'BadgeClass';
    $badge->{'extensions:badgetemplateB4E'} = get_extension_badgetemplate_b4e();
    $badge->tags = $digitalcert->tags;
    $badge->criteria = $digitalcert->criteria;
    $badge->image = get_badgeimage_base64($cm);

    return $badge;
}

function get_badgeimage_base64($cm) {
    $context = context_module::instance($cm->id);

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_ilddigitalcert', 'content', 0);
    foreach ($files as $file) {
        $storedfile = $fs->get_file(
            $context->id,
            'mod_ilddigitalcert',
            'content',
            0,
            $file->get_filepath(),
            $file->get_filename()
        );
        if ($file->get_filename() != '.') {
            break;
        }
    }
    if (isset($file)) {
        $content = $file->get_content();
    } else {
        return '';
    }
    // TODO: Image is not saved while adding a new certificate ti the course. you have to edit the activity again.
    $imgbase64 = 'data:' . $storedfile->get_mimetype() . ';base64,' . base64_encode($content);
    return $imgbase64;
}

function get_issuerimage_base64($issuerid) {
    $context = context_system::instance();

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_ilddigitalcert', 'issuer', $issuerid);
    foreach ($files as $file) {
        if ($file->get_filename() != '.') {
            $storedfile = $fs->get_file(
                $context->id,
                'mod_ilddigitalcert',
                'issuer',
                $issuerid,
                $file->get_filepath(),
                $file->get_filename()
            );
            break;
        }
    }
    $content = $file->get_content();
    $imgbase64 = 'data:' . $storedfile->get_mimetype() . ';base64,' . base64_encode($content);
    return $imgbase64;
}

function get_extension_contract_b4e() {
    require_once('web3lib.php');
    global $contexturl;
    $extension = new stdClass();

    $extension->{'@context'} = $contexturl->contractB4E;
    $extension->type = array('Extension', 'ContractB4E');
    $extension->abi = get_contract_abi('CertMgmt');
    $extension->address = get_contract_address('CertMgmt');
    $extension->node = get_contract_url('CertMgmt');

    return $extension;
}

function get_extension_badgetemplate_b4e() {
    global $contexturl;
    $extension = new stdClass();

    $extension->{'@context'} = $contexturl->badgetemplateB4E;
    $extension->type = array('Extension', 'BadgeTemplateB4E');

    return $extension;
}

function get_extension_badgeexpertise_b4e($expertise) {
    global $contexturl;
    $extension = new stdClass();
    $extension->{'@context'} = $contexturl->badgeexpertiseB4E;
    $extension->type = array('Extension', 'BadgeExpertiseB4E');
    $extension->expertise = $expertise;
    return $extension;
}

function get_issuer($issuerid) {
    global $DB, $contexturl, $CFG;
    $issuerrecord = $DB->get_record('ilddigitalcert_issuer', array('id' => $issuerid));

    $issuer = new stdClass();
    $issuer->description = $issuerrecord->description;
    $issuer->{'extensions:addressB4E'} = get_extension_address_b4e($issuerrecord);
    $issuer->email = $issuerrecord->email;
    $issuer->name = $issuerrecord->name;
    $issuer->url = $issuerrecord->url;
    $issuer->{'@context'} = $contexturl->openbadges;
    $issuer->type = 'Issuer';
    $issuer->id = $CFG->wwwroot . '/mod/ilddigitalcert/edit_issuers.php?action=edit&id=' . $issuerrecord->id;
    $issuer->image = get_issuerimage_base64($issuerid);

    return $issuer;
}

function get_extension_institutiontoken_ild($token) {
    global $contexturl;
    $extension = new stdClass();
    $extension->{'@context'} = $contexturl->institutionTokenILD;
    $extension->type = array('Extension', 'InstitutionTokenILD');
    $extension->institutionToken = $token;
    return $extension;
}

function get_extension_assertionpage_b4e($cmid, $certmetadatajson) {
    global $DB, $contexturl;
    $cm = $DB->get_record('course_modules', array('id' => $cmid));
    $extension = new stdClass();
    $extension->{'@context'} = $contexturl->assertionpageB4E;
    $extension->type = array('Extension', 'AssertionPageB4E');
    $extension->assertionpage = base64_encode(get_certificatehtml($cm->instance, $certmetadatajson));
    return $extension;
}

function get_extension_address_b4e($issuerrecord) {
    global $contexturl;
    $address = new stdClass();

    $address->location = $issuerrecord->location;
    $address->zip = $issuerrecord->zip;
    $address->street = $issuerrecord->street;
    $address->{'@context'} = $contexturl->addressB4E;
    $address->type = array('Extension', 'AddressB4E');
    if ($issuerrecord->pob != 0) {
        $address->pob = $issuerrecord->pob;
    }

    return $address;
}

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

function get_institution($ipfshash) {
    $institution = new stdClass();

    $ipfsurl = 'https://ipfs.io/ipfs/' . $ipfshash;

    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ipfsurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout in seconds.
        // Also don't forget to enlarge time execution of php script self.
        // Backup: set_time_limit(0); // to infinity for example.
        // Backup: curl_setopt($ch, CURLOPT_USERAGENT, 'MyBot/1.0 (http://www.mysite.com/)'); //.

        $jsonresult = curl_exec($ch);

        /*
        if ($jsonresult === false) {
            // Backup: print_object('1: '.curl_error($ch)); //.
            // Operation timed out after 10011 milliseconds with 0 out of -1 bytes received.
        } else if(($statuscode=curl_getinfo($ch, CURLINFO_HTTP_CODE)) == 200){
            // All fine!
            // JSON decode?
        } else {
            $error = 'could not reach ...! HTTP-Statuscode: '.$statuscode;
        }
        */
        curl_close($ch);
    } catch (Exception $e) {
        echo 'error'; // TODO: print error.
    }

    $institution = json_decode($jsonresult);

    return $institution;
}

function add_certifier($userid, $useraddress, $adminpk) {
    require_once('web3lib.php');
    global $DB;
    // Check if user already exists in user_preferences.
    if ($userpref = $DB->get_record('user_preferences', array('name' => 'mod_ilddigitalcert_certifier', 'userid' => $userid))) {
        if (strpos($userpref->value, 'not_registered_pk') === false) {
            print_error(
                'user_is_already_certifier',
                'mod_ilddigitalcert',
                new moodle_url('/mod/ilddigitalcert/edit_certifiers.php')
            );
        }
    }
    // Check if $useraddress already exists in user_preferences.
    if ($userpref = $DB->get_record('user_preferences', array('name' => 'mod_ilddigitalcert_certifier', 'value' => $useraddress))) {
        print_error('address_is_already_used', 'mod_ilddigitalcert', new moodle_url('/mod/ilddigitalcert/edit_certifiers.php'));
    }
    // Add to blockchain.
    add_certifier_to_blockchain($useraddress, $adminpk);
    // If success (isAccreditedCertifier).
    $start = time();
    $ac = false;
    while (1) {
        $now = time();
        if ($ac = is_accredited_certifier($useraddress)) {
            break;
        }
        if ($now - $start > 30) {
            break;
        }
    }
    if ($ac) {
        // Add Userpref.
        set_user_preference('mod_ilddigitalcert_certifier', $useraddress, $userid);
        return true;
    } else {
        print_error(
            'error_while_adding_certifier_to_blockchain',
            'mod_ilddigitalcert',
            new moodle_url('/mod/ilddigitalcert/edit_certifiers.php')
        );
    }
}

// Delete certifier.
function remove_certifier($userprefid, $adminpk) {
    global $DB;
    if ($userpref = $DB->get_record('user_preferences', array('id' => $userprefid))) {
        $useraddress = $userpref->value;
        if (is_accredited_certifier($useraddress)) {
            // Remove certifier from blockchain.
            remove_certifier_from_blockchain($useraddress, $adminpk);
            $start = time();
            $ac = true;
            while (1) {
                $now = time();
                $ac = is_accredited_certifier($useraddress);
                if (!$ac) {
                    break;
                }
                if ($now - $start > 30) {
                    break;
                }
            }
            if ($ac) {
                print_error(
                    'error_while_removing_certifier_from_blockchain',
                    'mod_ilddigitalcert',
                    new moodle_url('/mod/ilddigitalcert/edit_certifiers.php')
                );
            } else {
                // If success, delete from userpref.
                unset_user_preference('mod_ilddigitalcert_certifier', $userpref->userid);
                // TODO Email to ex-certifier.
                return true;
            }
        } else {
            unset_user_preference('mod_ilddigitalcert_certifier', $userpref->userid);
            print_error(
                'certifier_already_removed_from_blockchain',
                'mod_ilddigitalcert',
                new moodle_url('/mod/ilddigitalcert/edit_certifiers.php')
            );
        }
    } else {
        print_error(
            'certifier_already_removed_from_blockchain',
            'mod_ilddigitalcert',
            new moodle_url('/mod/ilddigitalcert/edit_certifiers.php')
        );
    }
}

// Existiert eine Zertifizierungsstellen-Adresse bereits in der mdl_ilddigitalcert_issuer?
function institution_address_exists($address) {
    global $DB;
    if ($DB->get_record('ilddigitalcert_issuer', array('address' => $address))) {
        return true;
    }
    return false;
}

function get_issuer_name_from_address($institutionaddress) {
    global $DB;
    if ($issuers = $DB->get_records('ilddigitalcert_issuer', array('address' => $institutionaddress))) {
        if (count($issuers) > 1) {
            print_error('found_address_more_than_one_times', 'mod_ilddigitalcert');
        }
        foreach ($issuers as $issuer) {
            return $issuer->name;
        }
    }
    print_error('address_not_found', 'mod_ilddigitalcert');
}

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

function download_file($fileid) {
    global $DB, $CFG;

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

function debug_email($to, $message, $debugobject = null) {
    global $USER;
    $subject = 'debug';
    $from = $USER;
    if (isset($debugobject)) {
        ob_start();
        var_dump($debugobject);
        $message .= ob_get_contents();
        ob_end_clean();
    }
    email_to_user($to, $from, $subject, $message, $message);
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
