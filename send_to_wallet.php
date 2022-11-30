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
 * Prints the view for
 * managing connection/relationship to the data wallet of the digital campus
 * and
 * sending the digital certificate to the data wallet
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once('dcconnectorlib.php');

$id = optional_param('id', 0, PARAM_INT); // Issued certificate id.

require_login();
if (isguestuser()) {
    redirect($CFG->wwwroot.'/login/');
}

if ($id != 0) {
    if (!$issuedcert = $DB->get_record('ilddigitalcert_issued', array('cmid' => $id, 'userid' => $USER->id))) {
        throw new moodle_exception(get_string('wrongcertidornotloggedin', 'mod_ilddigitalcert'));
    }
} else {
    throw new moodle_exception(get_string('missingcertid', 'mod_ilddigitalcert'));
}

$context = context_system::instance();

$PAGE->set_url('/mod/ilddigitalcert/send_to_wallet.php', array('id' => $id));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('send_to_wallet', 'mod_ilddigitalcert'));
$PAGE->set_heading(get_string('send_to_wallet', 'mod_ilddigitalcert'));
$PAGE->set_context($context);

echo $OUTPUT->header();

$host = get_config('mod_ilddigitalcert', 'dchost');
$xapikey = get_config('mod_ilddigitalcert', 'dcxapikey');
$dcconnectoraddress = get_config('mod_ilddigitalcert', 'dcconnectoraddress');
// $dcconnectorid = get_config('mod_ilddigitalcert', 'dcconnectorid');
$walletid = get_user_preferences('mod_ilddigitalcert_wallet_id', 'error', $USER->id);
$relationshipid = get_user_preferences('mod_ilddigitalcert_relationship_id', 'error', $USER->id);

if ($walletid != 'error' and $relationshipid != 'error') {
    $url = new moodle_url('/mod/ilddigitalcert/send_to_wallet.php?id='.$id);
    $mform = new mod_ilddigitalcert\output\form\dcconnectorconfirm_form($url);
    if ($fromform = $mform->get_data()) {
        // Check if relationship exists between our dcconnectorid and wallet id.
        $relresult = callAPI('GET', $host.'/api/v2/Relationships/'.$relationshipid, false, $xapikey);
        $relresult = json_decode($relresult);
        
        if (isset($relresult->result->peer) and
                $relresult->result->peer == $walletid) {
            // Upload pdf.
            $modulecontext = context_module::instance($id);
            /*
            $filename = $CFG->wwwroot.'/mod/ilddigitalcert/download.php?id='.$modulecontext->id.
            '&icid='.$issuedcert->id.'&cmid='.$id.'&download=pdf';
            $fileid = uploadpdf($filename, $issuedcert->name);
            */

            //*  comment: Disable sending file
            $pdfcontent = get_pdfcontent($modulecontext->id, $issuedcert->id);
            $fileid = uploadpdf($pdfcontent, $issuedcert->name);
            if (!$fileid) {
                throw new moodle_exception(get_string('file_upload_error', 'mod_ilddigitalcert'));
            }
            //print_object($fileid);die();
            // Send cert to wallet.
            $messagedata = new stdClass();
            $messagedata->recipients = array($walletid);
            $messagedata->content = new stdClass();
            $messagedata->content->{'@type'} = 'Mail';
            $messagedata->content->body = get_string('message_sendtowallet_body', 'mod_ilddigitalcert');
            $messagedata->content->subject = get_string('message_sendtowallet_subject', 'mod_ilddigitalcert');
            $messagedata->content->to = array($walletid);
            $messagedata->attachments = array($fileid);
            $messagedata = json_encode($messagedata, JSON_PRETTY_PRINT);

            $msgresult = callAPI('POST', $host.'/api/v2/Messages', $messagedata, $xapikey);
            $msgresult = json_decode($msgresult);
            if (isset($msgresult->error)) {
                throw new coding_exception(get_string('msg_send_error', 'mod_ilddigitalcert'));
            }
            // TODO Display success and link back to cert.
            //*/

            //*
            // send attributes (Study.planning.field_of_interest) to user wallet
            //$courseid = $issuedcert->courseid;
            //$value = get_subjectarea($courseid);
            $attributes = get_dcattributes($id);
            if (count($attributes) > 0) {
                $reason = get_string('study_field', 'mod_ilddigitalcert');
                //$msgresult = send_attributes('THL.FLL.study_field', $value, $walletid, $reason, $host, $xapikey);
                $msgresult = send_attributes($attributes, $walletid, $reason, $host, $xapikey);
                $msgresult = json_decode($msgresult);
                if (isset($msgresult->error)) {
                    throw new coding_exception(get_string('msg_send_error', 'mod_ilddigitalcert'));
                }
            }
            //*/
            echo '<p>'.get_string('send_certificate_to_wallet_success', 'mod_ilddigitalcert').'</p>';
            echo '<p>'.html_writer::link(
                new moodle_url('/mod/ilddigitalcert/view.php?id='.$id),
                get_string('previous')).'</p>';
        } else {
            throw new coding_exception(get_string('wrong_relationship', 'mod_ilddigitalcert'));
        }
    } else if ($mform->is_cancelled()) {
        redirect($CFG->wwwroot.'/mod/ilddigitalcert/view.php?id='.$id);
    } else {
        // Let user confirm.
        echo '<p>'.get_string('send_certificate_to_wallet', 'mod_ilddigitalcert').'</p>';
        $mform->display();
    }
} else {
    $templateid = get_user_preferences('mod_ilddigitalcert_template_id', 'error', $USER->id);
    if ($templateid != 'error') { // TODO check if template is not expired!!!
        // If template id exists.
        echo '<p>'.get_string('scan_qr_code', 'mod_ilddigitalcert').'</p>';
        // ... /RelationshipTemplates/<templateid>/Token [image] (siehe Header).
        //$image = callAPI('POST', $host.'/api/v2/RelationshipTemplates/Own/'.$templateid.'/Token', '{}', $xapikey, true);
        $image = callAPI('GET', $host.'/api/v2/RelationshipTemplates/'.$templateid, '', $xapikey, true);
        $image = base64_encode($image);
        echo '<p><img src="data:image/png;base64,'.$image.'"/></p>';
        // Polling...
        echo '<p id="poll-info" style="color:black;display:none;">'.get_string('waiting_for_request', 'mod_ilddigitalcert').'</p>';
        echo '<script src="./js/dcc.js"></script>';
    } else {
        // If no template id exists.
        // create Identity Attribute
        $data = '{
            "content": {
                "@type": "IdentityAttribute",
                "owner": "'.$dcconnectoraddress.'",
                "value": {
                    "@type": "DisplayName",
                    "value": "Connector Tutorial"
                }
            }
        }';
        $result = callAPI('POST', $host.'/api/v2/Attributes', $data, $xapikey);
        if (isset($result->error)) {
            throw new coding_exception($result->error->message);
        }
        else {
            //print_object(json_encode(json_decode($result), JSON_PRETTY_PRINT));die();
            $attributeid = json_decode($result)->result->id;
        }
        $data = file_get_contents('request_items.json');
        $datajson = json_decode($data);
        $issuername = 'TH Lübeck'; // TODO get name from e.g. moodle site name
        if ($cm = $DB->get_record('course_modules', array('id' => $id))) {
            if ($cert = $DB->get_record('ilddigitalcert', array('id' => $cm->instance))) {
                if ($issuer = $DB->get_record('ilddigitalcert_issuer', array('id' => $cert->issuer))) {
                    $issuername = $issuer->name;
                }
            }
        }
        $datajson->items[0]->items[0]->attribute->value->value = $issuername;
        $datajson->items[0]->items[0]->sourceAttributeId = $attributeid;
        //print_object($datajson);die();
        //$request_items = json_encode($datajson);

        $data = file_get_contents('relationship_template.json');
        
        $data = json_decode($data);
        //print_object($data);die();
        $data->expiresAt = date('c', time() + 60 * 60 * 24);
        $data->content->onNewRelationship = $datajson;
        //print_object($data);die();
        $data = json_encode($data);
        //print_object(json_encode(json_decode($data), JSON_PRETTY_PRINT));die();
        $result = callAPI('POST', $host.'/api/v2/RelationshipTemplates/Own', $data, $xapikey);
        $result = json_decode($result);
        //print_object($result);die();
        if (isset($result->error)) {
            throw new coding_exception($result->error->message);
        }
        set_user_preference('mod_ilddigitalcert_template_id', $result->result->id, $USER->id);
        redirect($CFG->wwwroot.'/mod/ilddigitalcert/send_to_wallet.php?id='.$id);
    }
}

echo $OUTPUT->footer();
