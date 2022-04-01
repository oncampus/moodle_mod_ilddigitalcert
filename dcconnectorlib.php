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
 * Internal library of digital campus connector functions
 * for module ilddigitalcert
 *
 * @package     mod_ilddigitalcert
 * @copyright   2021 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/locallib.php');

 /**
  * Checks wether request information matches the info of the current user.
  *
  * @param object $request
  * @return bool True if request info matches the moodle users info, else false.
  */
function checkrequest($request) {
    global $USER;
    // Check if firstname, lastname and email match with moodle user.
    /*
    $firstname = $request->content->content->attributes->{'Person.givenName'}->value;
    $lastname = $request->content->content->attributes->{'Person.familyName'}->value;
    $email = $request->content->content->attributes->{'Comm.email'}->value;
    */
    $firstname = $request->changes[0]->request->content->attributes->{'Person.givenName'}->value;
    $lastname = $request->changes[0]->request->content->attributes->{'Person.familyName'}->value;
    $email = $request->changes[0]->request->content->attributes->{'Comm.email'}->value;
    /*
    print_object('$USER: '.$USER->firstname.' '.$USER->lastname.' '.$USER->email);
    print_object('$request: '.$firstname.' '.$lastname.' '.$email);
    */
    if (strtolower($USER->firstname) == strtolower($firstname) and
       strtolower($USER->lastname) == strtolower($lastname) and
       strtolower($USER->email) == strtolower($email)) {
        return true;
    }
    return false;
}

/**
 * Executes an api call.
 *
 * @param string $method
 * @param string $url
 * @param mixed|callable $data
 * @param string $xapikey
 * @param boolean $image
 * @return string|bool API response.
 */
function callapi($method, $url, $data, $xapikey, $image = false) {
    $curl = curl_init();
    switch ($method){
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
          break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
          break;
        default:
            if ($data) {
                $url = sprintf("%s?%s", $url, http_build_query($data));
            }
    }
    curl_setopt($curl, CURLOPT_URL, $url);
    $headerarray = array('X-API-KEY: '.$xapikey,
                         'Content-Type: application/json');
    if ($image) {
        $headerarray[] = 'Accept: image/png';
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headerarray);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    $result = curl_exec($curl);
    if (!$result) {
        throw new moodle_exception("Connection Failure");
    }
    curl_close($curl);
    return $result;
}

/**
 * Executes an api call meant for sending a pdf file.
 *
 * @param string $filename
 * @param string $title
 * @param string $description
 * @param string $url
 * @param string $xapikey
 * @return string|bool|void
 */
function callapipdfupload($filename, $title, $description, $url, $xapikey) {
    $ch = curl_init();
    $headers = array(
      'X-API-KEY: ' . $xapikey,
    'Content-Type:multipart/form-data'
    ); // CURL headers for file uploading.
    $tenyearslater = intval(date('Y', time())) + 10;
    $fields = [
      'file' => new \CurlFile($filename, 'application/pdf', $filename),
      'description' => $description,
      'title' => $title,
      'expiresAt' => $tenyearslater.'-01-01T00:00:00.000Z'
    ];
    $options = array(
      CURLOPT_URL => $url,
      CURLOPT_POST => 1,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_POSTFIELDS => $fields,
      CURLOPT_RETURNTRANSFER => true
    ); // CURL options.
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    if (!curl_errno($ch)) {
        $info = curl_getinfo($ch);
        if ($info['http_code'] == 200) {
            $errmsg = "File uploaded successfully";
        }
        return $result;
    } else {
        $errmsg = curl_error($ch);
    }
    curl_close($ch);
}

/**
 * Sends a pdf specified by its $filename to the DC-connector.
 *
 * @param string $pdf_content File content to be uploaded.
 * @param string $certname The name of the certificate store in the file.
 * @return int|boolean On successful upload returns the id by witch the certificate file is identified by the connector.
 * Returns false if the process was unsuccessful.
 */
function uploadpdf($pdfcontent, $certname) {
    global $CFG;
    $host = get_config('mod_ilddigitalcert', 'dchost');
    $xapikey = get_config('mod_ilddigitalcert', 'dcxapikey');
    $title = $certname;
    $description = get_string('cert_file_description', 'mod_ilddigitalcert');
    $filecontent = $pdfcontent;
    if (!is_dir($CFG->dataroot.'/temp/ilddigitalcert')) {
        mkdir($CFG->dataroot.'/temp/ilddigitalcert', 0775);
    }
    if (!is_dir($CFG->dataroot.'/temp/ilddigitalcert/dcconnector')) {
        mkdir($CFG->dataroot.'/temp/ilddigitalcert/dcconnector', 0775);
    }
    $filename = $CFG->dataroot.'/temp/ilddigitalcert/dcconnector/'.$certname.'_'.uniqid().'.pdf';
    file_put_contents($filename, $filecontent);
    $uploadresult = callAPIpdfUpload($filename, $title, $description, $host.'/api/v1/Files/Own', $xapikey);
    unlink($filename);
    $resultobj = json_decode($uploadresult);
    if (isset($resultobj->result->id)) {
        $newfileid = $resultobj->result->id;
        return $newfileid;
    }
    return false;
}

/**
 * Creates a pdf certificate and returns the pdf as a string.
 *
 * @param int $icid Id of an issued certificate record.
 * @return string PDF certificate.
 */
function get_pdfcontent($modulecontextid, $icid) {
    global $DB;
    // Prepare certificate data for download.
    $certificaterecord = $DB->get_record('ilddigitalcert_issued', array('id' => $icid), '*', MUST_EXIST);
    $metacertificate = mod_ilddigitalcert\bcert\certificate::from_ob($certificaterecord->metadata);

    $pdf = create_pdf_certificate($certificaterecord, $metacertificate);

    // Return the content of the pdf file to send it to wallet.
    return $pdf->Output('', 'S');
}

/**
 * Send an attribute change request to a dc wallet.
 *
 * @param string $name Name of attribute.
 * @param string $value Value of attribute.
 * @param string $walletid Id of a DC wallet.
 * @param string $reason Reason for the attribute change request.
 * @param string $url The url of the DC Connector.
 * @param string $xapikey API Key.
 * @return string Returns the response of the http request.
 */
function send_attribute($name, $value, $walletid, $reason, $url, $xapikey) {
    $attribute = new stdClass();
    $attribute->{'@type'} = 'Attribute';
    $attribute->name = $name;
    $attribute->value = $value;
    $attribute->validFrom = date('Y-m-d', time());

    $request = new stdClass();
    $request->{'@type'} = 'AttributesChangeRequest';
    $request->reason = $reason;
    $request->attributes[] = $attribute;
    $request->applyTo = $walletid;

    $messagedata = new stdClass();
    $messagedata->recipients = array($walletid);
    $messagedata->content->{'@type'} = 'RequestMail';
    $messagedata->content->to = array($walletid);
    $messagedata->content->subject = get_string('subject_new_attribute', 'mod_ilddigitalcert');
    $messagedata->content->body = get_string('body_new_attribute', 'mod_ilddigitalcert');
    $messagedata->content->requests[] = $request;

    $messagedata = json_encode($messagedata, JSON_PRETTY_PRINT);
    $msgresult = callAPI('POST', $url.'/api/v1/Messages', $messagedata, $xapikey);
    return $msgresult;
}

/**
 * Gets the subjectarea of a course.
 *
 * @param int $courseid
 * @return string
 */
function get_subjectarea($courseid) {
    global $DB;
    $subjectarea = 'Not defined';
    if ($result = $DB->get_record('user_info_field', array('shortname' => 'subjectareas'))) {
        $subjectareas = explode(PHP_EOL, $result->param1);
        if (count($subjectareas) > 0) {
            if ($result = $DB->get_record('ildmeta', array('courseid' => $courseid))) {
                if ($result->subjectarea < count($subjectareas)) {
                    $subjectarea = $subjectareas[$result->subjectarea];
                }
            }
        }
    }
    return $subjectarea;
}
