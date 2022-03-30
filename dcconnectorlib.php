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

 /**
  * Checks wether request information matches the info of the current user.
  *
  * @param object $request
  * @return bool True if request info matches the moodle users info, else false.
  */
function checkrequest($request) {
    global $USER;
    // Check if firstname, lastname and email match with moodle user.
    $firstname = $request->content->content->attributes->{'Person.givenName'}->value;
    $lastname = $request->content->content->attributes->{'Person.familyName'}->value;
    $email = $request->content->content->attributes->{'Comm.email'}->value;
    if ($USER->firstname == $firstname and
       $USER->lastname == $lastname and
       $USER->email == $email) {
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
        die("Connection Failure");
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
 * @param string $filename File to be uploaded.
 * @param string $certname The name of the certificate store in the file.
 * @return int|boolean On successful upload returns the id by witch the certificate file is identified by the connector.
 * Returns false if the process was unsuccessful.
 */
function uploadpdf($filename, $certname) {
    global $CFG;
    $host = get_config('mod_ilddigitalcert', 'dchost');
    $xapikey = get_config('mod_ilddigitalcert', 'dcxapikey');
    $title = $certname;
    $description = get_string('dcconnector_pdfuploaddesc', 'mod_ilddigitalcert');
    $filecontent = file_get_contents($filename);
    if (!is_dir($CFG->dataroot.'/temp/ilddigitalcert')) {
        mkdir($CFG->dataroot.'/temp/ilddigitalcert', 0775);
    }
    if (!is_dir($CFG->dataroot.'/temp/ilddigitalcert/dcconnector')) {
        mkdir($CFG->dataroot.'/temp/ilddigitalcert/dcconnector', 0775);
    }
    $filename = $CFG->dataroot.'/temp/ilddigitalcert/dcconnector/'.$certname.'_'.uniqid().'.pdf';
    file_put_contents($filename, $filecontent);
    $uploadresult = callAPIpdfUpload($filename, $title, $description, $host.'/api/v1/Files', $xapikey);
    unlink($filename);
    $resultobj = json_decode($uploadresult);
    if (isset($resultobj->result->id)) {
        $newfileid = $resultobj->result->id;
        return $newfileid;
    }
    return false;
}
