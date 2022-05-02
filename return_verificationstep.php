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
 * Returns certificate data for the verification process.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once('locallib.php');
require_once('web3lib.php');

require_login();

$action = optional_param('action', '', PARAM_RAW);
$meta = optional_param('meta', '', PARAM_RAW);
$hash = optional_param('hash', '', PARAM_RAW);
$base64string = optional_param('base64String', '', PARAM_RAW);
$institutionprofile = optional_param('institution_profile', '', PARAM_RAW);
$verificationmethod = optional_param('verificationMethod', 'hash', PARAM_RAW);

if ($action == 'meta' and $meta != '') {
    $certhash = calculate_hash($meta);
    echo $certhash;
} else if ($action == 'hash' and $hash != '') {
    try {
        $cert = get_certificate($hash);
    } catch (InvalidArgumentException $e) {
        $cert = new stdClass();
    }
    $cert->startingDate = date('d.m.Y', intval($cert->startingDate));
    if (intval($cert->endingDate) != 0) {
        $cert->endingDate = date('d.m.Y', intval($cert->endingDate));
    } else {
        $cert->endingDate = 'false';
    }
    echo json_encode($cert);

    // If the value of $cert->institution equals the 0x00... value,
    // the hash could not be found in the blockchain.Therefore it is invalid.

    // Log verification_completed event.
    $event = \mod_ilddigitalcert\event\verification_completed::create(
        array(
            'context' => context_system::instance(),
            'other' => array(
                'verification_result' => filter_var($cert->valid, FILTER_VALIDATE_BOOLEAN),
                'verification_method' => $verificationmethod,
            )
        )
    );
    $event->trigger();
} else if ($action == 'validateJSON' and !empty($_FILES['file']['name']) and
    ($_FILES['file']['type'] == 'application/json' or strpos($_FILES['file']['name'], '.bcrt') !== false)) {
    $file = $_FILES['file'];
    // TODO: validate.
    echo file_get_contents($file['tmp_name']);
} else if ($action == 'validateEDCI' and !empty($_FILES['file']['name']) and
    ($_FILES['file']['type'] == 'application/xml' or strpos($_FILES['file']['name'], '.xml') !== false)) {
    $file = $_FILES['file'];
    // TODO: validate.
    $edci = file_get_contents($file['tmp_name']);
    $json = \mod_ilddigitalcert\bcert\certificate::from_edci($edci)->get_ob();
    echo $json;
} else if ($action == 'pdf' and !empty($_FILES['file']['name']) and $_FILES['file']['type'] == 'application/pdf') {
    // Verifing validity of a pdf file.
    $file = $_FILES['file'];
    $pdf = $file['tmp_name'];
    // Get Attachments.
    $attachmentlistresult = shell_exec('pdfdetach -list '.$pdf.' 2>&1');
    $attachmentlist = explode("\n", $attachmentlistresult);
    $attachments = array();
    $n = -1;

    $bcrtindex = -1;
    $xmlindex = -1;
    $error = '';

    // Check if there is only one file per permitted file type, else generate error.
    foreach ($attachmentlist as $attachment) {
        $n++;

        $entry = explode(': ', $attachment);
        $value = $entry[1];

        $filename = basename($entry[1]);

        // More than 1 attachment per allowed file type .bcrt and .xml? -> error.
        if (substr($filename, -5) === '.bcrt') {
            if ($bcrtindex === -1) {
                $bcrtindex = $n;
            } else {
                $error = 'error: too many .bcrt attachements detected';
            }
        } else if (substr($filename, -4) === '.xml') {
            if ($xmlindex === -1) {
                $xmlindex = $n;
            } else {
                $error = 'error: too many .xml attachements detected';
            }
        }
    }
    if ($error !== '') {
        echo $error;
    } else {
        // TODO: Also return the edci attachement for verification.
        // Currently only the .bcrt attachemeent is valdiated when a pdf validation is requested.
        // Returns the openBadge certificate/.bcrt file attachement content.
        echo get_attachement_content($bcrtindex, $pdf);
    }
} else if ($action == 'baseString' and $base64string != '') {
    echo base64_decode($base64string);
} else if ($action == 'institution_profile' and $institutionprofile != '') {
    $institution = new stdClass();
    $ipfshash = get_ipfs_hash($institutionprofile);
    $institution = get_institution($ipfshash);
    // TODO return object with image and url.
    if (isset($institution->url)) {
        $institution->description = $institution->name;
    } else {
        if ($meta != '') {
            $metaobj = json_decode($meta);
            $institution->url = $metaobj->badge->issuer->url;
            $institution->name = $metaobj->badge->issuer->name;
            $institution->description = $metaobj->badge->issuer->description;
            $institution->image = $metaobj->badge->issuer->image;
            $institution->meta = $meta;
        }
    }
    echo json_encode($institution);
} else if ($action == 'cert' and $hash != '') {

    if ($result = $DB->get_record('ilddigitalcert_issued', array('certhash' => $hash))) {
        // TODO show only if token exists.
        $metadata = json_decode($result->metadata);
        $metabadge = new stdClass();
        $metabadge->issuer = $metadata->badge->issuer;
        $metaresult = new stdClass();
        $metaresult->badge = $metabadge;
        $metaresult->{'extensions:assertionpageB4E'}->assertionpage = $metadata->{'extensions:assertionpageB4E'}->assertionpage;
        $metadata = json_encode($metaresult);
        echo $metadata;
    } else {
        echo null;
    }

} else {
    echo 'result - action: '.$action.
         ', meta: '.$meta.
         ', hash: '.$hash.
         ', base64: '.$base64string.
         ', institution_profile: '.$institutionprofile.
         ', file: '.$_FILES['file']['name'];
}

function get_attachement_content($index, $pdf) {
    global $CFG;
    if (!is_dir($CFG->dataroot.'/temp/ilddigitalcert')) {
        mkdir($CFG->dataroot.'/temp/ilddigitalcert', 0775);
    }
    if (!is_dir($CFG->dataroot.'/temp/ilddigitalcert/attachments')) {
        mkdir($CFG->dataroot.'/temp/ilddigitalcert/attachments', 0775);
    }
    $path = $CFG->dataroot.'/temp/ilddigitalcert/attachments/temp'.$index;
    shell_exec('pdfdetach -save ' . $index . ' -o '.$path.' '.$pdf.' 2>&1');
    if (!isset($detachresult)) {
        $filecontent = file_get_contents($path);
        // Delete file.
        unlink($path);
        // Return attachements file content.
        return $filecontent;
    } else {
        throw new \coding_exception('Could\'nt detach attachement from pdf.');
        die();
    }
}
