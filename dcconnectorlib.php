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

function callAPI($method, $url, $data, $xapikey, $image = false){
    $curl = curl_init();
    switch ($method){
       case "POST":
          curl_setopt($curl, CURLOPT_POST, 1);
          if ($data)
             curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
          break;
       case "PUT":
          curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
          if ($data)
             curl_setopt($curl, CURLOPT_POSTFIELDS, $data);			 					
          break;
       default:
          if ($data)
             $url = sprintf("%s?%s", $url, http_build_query($data));
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
    if(!$result){die("Connection Failure");}
    curl_close($curl);
    return $result;
}

function callAPIpdfUpload($filename, $title, $description, $url, $xapikey) {
   $ch = curl_init();
   $headers = array('X-API-KEY: '.$xapikey,
                    'Content-Type:multipart/form-data'); // cURL headers for file uploading
   $tenyearslater = intval(date('Y', time())) + 10;
   $fields = [
       'file' => new \CurlFile($filename, 'application/pdf', $filename),
       'description' => $description,
       'title' => $title,
       'expiresAt' => $tenyearslater.'-01-01T00:00:00.000Z'
   ];
   $options = array(
       CURLOPT_URL => $url,
       //CURLOPT_HEADER => true,
       CURLOPT_POST => 1,
       CURLOPT_HTTPHEADER => $headers,
       CURLOPT_POSTFIELDS => $fields,
       //CURLOPT_INFILESIZE => $filesize,
       CURLOPT_RETURNTRANSFER => true
   ); // cURL options
   curl_setopt_array($ch, $options);
   $result = curl_exec($ch);
   if(!curl_errno($ch)) {
       $info = curl_getinfo($ch);
       if ($info['http_code'] == 200)
           $errmsg = "File uploaded successfully";
       return $result;
   } else {
       $errmsg = curl_error($ch);
   }
   curl_close($ch);
}

function uploadpdf($filename, $certname) {
   global $CFG;
   $host = get_config('mod_ilddigitalcert', 'dchost');
   $xapikey = get_config('mod_ilddigitalcert', 'dcxapikey');
   $title = $certname;
   $description = 'PDF certificate file with embedded certificate metadata.'; // TODO Add text to lang files.
   $filecontent = file_get_contents($filename);
   if (!is_dir($CFG->dataroot.'/temp/ilddigitalcert')) {
      mkdir($CFG->dataroot.'/temp/ilddigitalcert', 0775);
   }
   if (!is_dir($CFG->dataroot.'/temp/ilddigitalcert/dcconnector')) {
      mkdir($CFG->dataroot.'/temp/ilddigitalcert/dcconnector', 0775);
   }
   $filename = $CFG->dataroot.'/temp/ilddigitalcert/dcconnector/'.$certname.'_'.uniqid().'.pdf';
   file_put_contents($filename, $filecontent);
   $upload_result = callAPIpdfUpload($filename, $title, $description, $host.'/api/v1/Files', $xapikey);
   unlink($filename);
   $resultobj = json_decode($upload_result);
   if (isset($resultobj->result->id)) {
      $newfileid = $resultobj->result->id;
      return $newfileid;
   }
   return false;
}
