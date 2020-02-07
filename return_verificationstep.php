<?php

require_once(__DIR__.'/../../config.php');
require_once('locallib.php');
require_once('web3lib.php');

$action = optional_param('action', '', PARAM_RAW);
$meta = optional_param('meta', '', PARAM_RAW);
$hash = optional_param('hash', '', PARAM_RAW);
$base64String = optional_param('base64String', '', PARAM_RAW);
$institution_profile = optional_param('institution_profile', '', PARAM_RAW);

if ($action == 'meta' and $meta != '') {
    $certhash = calculate_hash($meta);
    echo $certhash;
}
elseif ($action == 'hash' and $hash != '') {
    $cert = getCertificate($hash);
    echo json_encode($cert);
}
elseif ($action == 'validateJSON' and !empty($_FILES['file']['name']) and 
	($_FILES['file']['type'] == 'application/json' or strpos($_FILES['file']['name'], '.bcrt') !== false)) {
	$file = $_FILES['file'];
	// TODO: validate
	echo file_get_contents($file['tmp_name']);
}
elseif ($action == 'pdf' and !empty($_FILES['file']['name']) and $_FILES['file']['type'] == 'application/pdf') {
	$file = $_FILES['file'];
	$pdf = $file['tmp_name'];
	// get Attachments
	$attachment_list_result = `pdfdetach -list $pdf 2>&1`;
	$attachment_list = explode("\n",$attachment_list_result);
	$attachments = array();
	$n = 0;
	foreach ($attachment_list as $attachment) {
		if ($n == 0) {
			$n++;
			continue;
		}
			
		if ($attachment == "") {
			continue;
		}
		
		$entry = explode(': ', $attachment);
		$key = $entry[0];
		$value = $entry[1];
		$attachments[$key] = $value;
	}
	// more than 1 attachment? -> error
	if (count($attachments) != 1) {
		echo 'error';
	}
	else {
		$attachment_file = array_pop($attachments);
		$basename_attachment_file = basename($attachment_file);
		if (!is_dir($CFG->dataroot.'/temp/ilddigitalcert')) {
			mkdir($CFG->dataroot.'/temp/ilddigitalcert', 0775);
		}
		if (!is_dir($CFG->dataroot.'/temp/ilddigitalcert/attachments')) {
			mkdir($CFG->dataroot.'/temp/ilddigitalcert/attachments', 0775);
		}
		$path = $CFG->dataroot.'/temp/ilddigitalcert/attachments/'.$basename_attachment_file;
		$detach_result = `pdfdetach -save 1 -o $path $pdf 2>&1`;
		if (!isset($detach_result)) {
			$file_content = file_get_contents($path);
			$json = $file_content;
			// Datei wieder löschen
			unlink($path);
			// return metadata as json
			echo $json;
		}
		else {
			//echo 'error path: '.$path;
			echo 'error';
		}
	}
}
elseif ($action == 'baseString' and $base64String != '') {
	echo base64_decode($base64String);
}
elseif ($action == 'institution_profile' and $institution_profile != '') {
	$ipfs_hash = get_ipfs_hash($institution_profile);
	$institution = get_institution($ipfs_hash);
	// TODO object mit bild url usw zurückliefern
	if (isset($institution->url)) {
		$institution->description = $institution->name;
		echo json_encode($institution);
	}
	else {
		if ($meta != '') {
			$meta_obj = json_decode($meta);
			$institution = new stdClass();
			$institution->url = $meta_obj->badge->issuer->url;
			$institution->name = $meta_obj->badge->issuer->name;
			$institution->description = $meta_obj->badge->issuer->description;
			$institution->image = $meta_obj->badge->issuer->image;
			echo json_encode($institution);
		}
		else {
			echo null;
		}
	}
	
}
elseif ($action == 'cert' and $hash != '') {
	// TODO echo metadaten wenn cert/hash im system vorhanden
	if ($result = $DB->get_record('ilddigitalcert_issued', array('certhash' => $hash))) {
		echo $result->metadata;
	}
	else {
		echo null;
	}
}
else {
	echo 'result - action: '.$action.
		 ', meta: '.$meta.
		 ', hash: '.$hash.
		 ', base64: '.$base64String.
		 ', institution_profile: '.$institution_profile.
		 ', file: '.$_FILES['file']['name'];
}