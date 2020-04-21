<?php
require_once('schema.php');

function download_json($modulecontextid, $icid, $download) {
	global $DB, $CFG;

	$fs = get_file_storage();
	$stored_file = $fs->get_file($modulecontextid,
											  'mod_ilddigitalcert',
											  'metadata',
											  $icid,
											  '/',
											  'certificate.bcrt');
											  
	if ($download == 'json') {
		send_stored_file($stored_file, null, 0, true);
	}
	elseif ($download == 'pdf') {
		$hash = '';
		$filename = 'certificate';
		if ($issued_certificate = $DB->get_record('ilddigitalcert_issued', array('id' => $icid))) {
			$metadata_json = $issued_certificate->metadata;

			$salt = get_token($issued_certificate->institution_token);
			$metadata = json_decode($metadata_json);
			$metadata->{'extensions:institutionTokenILD'} = get_extension_institutionTokenILD($salt);
			$metadata_json = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

			$hash = calculate_hash($metadata_json);

			$filename = str_replace(' ', '_', $issued_certificate->name).'_'.
					$metadata->{'extensions:recipientB4E'}->givenname.'_'.
					$metadata->{'extensions:recipientB4E'}->surname.'_'.
					strtotime($metadata->issuedOn);
		}
		$content = $stored_file->get_content();

		require_once __DIR__ . '/vendor/autoload.php';

		$certificate = new \Mpdf\Mpdf(['mode' => 'utf-8', 'margin_top' => 0, 'margin_left' => 0, 'margin_right' => 0, 'margin_bottom' => 0, 'format' => [210, 297]]);
		//$certificate = new \Mpdf\Mpdf();
		$certificate->showImageErrors = true;

		$html = '<h1>Error</h1>';

		$json = $content;
		if (isset($json) and $json != '') {
			$jsonobj = json_decode($json);
			$html = base64_decode($jsonobj->{'extensions:assertionpageB4E'}->assertionpage);
		}
		
		/*
		//$certificate->AddPage();
		//$html .= get_pdf_footerhtml($hash);
		if (strpos($html, '<div id="zertifikat-page">') === 0) {

			$certificate->WriteHTML($html);
		}
		else {
			$certificate->WriteHTML($html);
			$certificate->WriteHTML(get_pdf_footerhtml($hash));
		}
		#*/

		$certificate->WriteHTML($html);
		$certificate->WriteHTML(get_pdf_footerhtml($hash));

		$fileid = $stored_file->get_id(); // fileid ermitteln

		$certificate->SetAssociatedFiles([[
			'name' => $filename.'.bcrt',
			'mime' => 'application/json',
			'description' => 'some description',
			'AFRelationship' => 'Alternative',
			'path' => $CFG->wwwroot.'/mod/ilddigitalcert/download_pdf.php?id='.$fileid
		]]);

		$certificate->Output($filename.'.pdf', 'I');

		return;
	}
}

function get_pdf_footerhtml($hash) {
	global $CFG;

	$verify_url = $CFG->wwwroot.'/mod/ilddigitalcert/verify.php';

	$html = '
		<div style="border: 0px solid #000;padding-top: 15px;padding-left: 8px;position:absolute;top:975px;left:0px;">
		<table class="items" width="50%" cellpadding="3" border="0">
				<tr>
					<td class="barcodecell" width="100px">
						<a href="'.$verify_url.'?hash='.$hash.'" style="color: rgb(0,0,0) !important;">
							<div>
								<barcode code="'.$CFG->wwwroot.'/mod/ilddigitalcert/verify.php?hash='.$hash.'" type="QR" class="barcode" size="1" error="M" disableborder="1" />
							</div>
						</a>
					</td>
					<td style="font-family: verdana;font-size: 7pt;">'.//color: rgb(112,112,111);">'.
						'<b>'.get_string('verify_authenticity', 'mod_ilddigitalcert').'</b><br/><br/>'.
						get_string('verify_authenticity_descr', 'mod_ilddigitalcert', array('url' => $verify_url, 'hash' => $hash)).'
					</td>
				</tr>
		</table>
		</div>';
	return $html;
}

function to_blockchain($issued_certificate, $fromuser, $pk) {
	global $DB, $CFG, $SITE;
	#/*
	require_once('web3lib.php');
	$pref = get_user_preferences('mod_ilddigitalcert_certifier', false, $fromuser);
	if (!$pref) {
		print_error('not_a_certifier', 'mod_ilddigitalcert');
	}
	else {
		if ($pref != get_address_from_pk($pk)) {
			print_error('wrong_private_key', 'mod_ilddigitalcert');
		}
	}
	#*/
	
	if (isset($issued_certificate->txhash)) {
		return false;
	}
	//signature hinzu
	$issued_certificate = add_signature($issued_certificate, $fromuser);
	//print_object($issued_certificate);
	$metadata = $issued_certificate->metadata;

	// save salt/token to file
	if (!$tokenid = save_token()) {
		$tokenid = 'error';
	}
	$salt = get_token($tokenid);
	$metadata = json_decode($metadata);
	$metadata->{'extensions:institutionTokenILD'} = get_extension_institutionTokenILD($salt);
	$metadata = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

	$hash = calculate_hash($metadata);
	// institutionToken wieder entfernen nur beim download (pdf und json) muss es zur datei hinzugefügt werden
	$metadata = json_decode($metadata);
	unset($metadata->{'extensions:institutionTokenILD'});
	$metadata = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

	//$startdate = $issued_certificate->timemodified;
	$startdate = strtotime(json_decode($metadata)->issuedOn);
	if (isset(json_decode($metadata)->expires)) {
		$enddate = strtotime(json_decode($metadata)->expires);
	}
	else {
		// Wenn kein expires angegeben wurde, automatisch auf 100 Jahre in der Zukunft setzen
		$enddate = 0;//time() + 60 * 60 * 24 * 365 * 100;
	}
	if ($enddate != 0 and $enddate <= $startdate) {
		return false;
	}
	$hashes = save_hash_in_blockchain($hash, $startdate, $enddate, $pk);
	if (isset($hashes->txhash)) {
		// verification hinzu
		$metadata = json_decode($metadata);
		$metadata->verification = new stdClass();
		$metadata->verification->{'extensions:verifyB4E'} = get_extension_verifyB4E($hash);

		//$metadata->salt = get_salt($token);
		$json = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		// hashes in db issued speichern
		$issued_certificate->certhash = $hashes->certhash;
		$issued_certificate->txhash = $hashes->txhash;
		$issued_certificate->metadata = $json;
		$issued_certificate->institution_token = $tokenid;
		$DB->update_record('ilddigitalcert_issued', $issued_certificate);
		//print_object(json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		
		if ($receiver = $DB->get_record('user', array('id' => $issued_certificate->userid))) {
			//email to user
			$from_user = core_user::get_support_user();
			$fullname = explode(' ', get_string('modulenameplural', 'mod_ilddigitalcert'));
			$from_user->firstname = $fullname[0];
			$from_user->lastname = $fullname[1];
			$subject = get_string('subject_new_digital_certificate', 'mod_ilddigitalcert');
			$a = new stdClass();
			$a->fullname = $receiver->firstname.' '.$receiver->lastname;
			$a->url = $CFG->wwwroot.'/mod/ilddigitalcert/view.php?id='.$issued_certificate->cmid;
			$a->from = $SITE->fullname;
			$message = get_string('message_new_digital_certificate', 'mod_ilddigitalcert', $a);
			$message_html = get_string('message_new_digital_certificate_html', 'mod_ilddigitalcert', $a);
			email_to_user($receiver, $from_user, $subject, $message, $message_html);
		}
		return true;
	}
	return false;
}

function save_token() {
	global $CFG;
	try {
		if (!is_dir($CFG->dataroot.'/ilddigitalcert_data')) {
			if (!mkdir($CFG->dataroot.'/ilddigitalcert_data', 0775)) {
				return false;
			}
		}
		$id = uniqid();
		$filename = $CFG->dataroot.'/ilddigitalcert_data/'.$id;
		while (file_exists($filename)) {
			$id = uniqid();
			$filename = $CFG->dataroot.'/ilddigitalcert_data/'.$id;
		}
		$token = bin2hex(random_bytes(32));
		if (!file_put_contents($filename, $token)) {
			return false;
		}
	}
	catch (Exception $e) {
		return false;
	}
	return $id;
}

function get_token($tokenid) {
	global $CFG;
	$filename = $CFG->dataroot.'/ilddigitalcert_data/'.$tokenid;
	if (file_exists($filename)) {
		return file_get_contents($filename);
	}
	else {
		return false;
	}
}

function add_signature($issued_certificate, $fromuser) {
	global $CONTEXT_URL;

	$metadata = $issued_certificate->metadata;
	$metadata_obj = json_decode($metadata);
	
	$extension = new stdClass();
	$extension->address = get_user_preferences('mod_ilddigitalcert_certifier', false, $fromuser); // fromuser blockchain adresse aus userpreferences holen
	$extension->email = $fromuser->email;
	$extension->surname = $fromuser->lastname;
	$extension->{'@context'} = $CONTEXT_URL->signatureB4E;
	$extension->role = 'Trainer/in'; // TODO aus profilefield Rolle mit der man in den Kurs eingeschrieben ist?
	$extension->certificationdate = date('c', time()); 
	$extension->type = array('Extension', 'SignatureB4E');
	$extension->givenname = $fromuser->firstname;
	if (isset($fromuser->city) and $fromuser->city != '') {
		$extension->certificationplace = $fromuser->city; // TODO ist das so korrekt?
	}
	
	$metadata_obj->{'extensions:signatureB4E'} = $extension;
	$metadata = json_encode($metadata_obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		
	$issued_certificate->metadata = $metadata;
	
	return $issued_certificate;
}

function calculate_hash($metadatajson) {
	$metadatajson = json_decode($metadatajson);
	$metadatajson = sort_obj($metadatajson);
	$metadatajson->recipient->hashed = false; // TODO kann es auch passieren, dass hier etwas anderes drinsteht? das geht sicher auch eleganter
	//verification entfernen (wenn bereits vorhanden)
	unset($metadatajson->{'extensions:verifyB4E'});
	unset($metadatajson->{'verification'}); // bleibt drin um abwärtskompatibel zu bleiben
	$metadatajson = json_encode($metadatajson, JSON_UNESCAPED_SLASHES);
	$hash = '0x'.hash('sha256', $metadatajson);
	return $hash;
}

function save_hash_in_blockchain($hash, $startdate, $enddate, $pk) {
	require_once('web3lib.php');
	
	$hashes = store_certificate($hash, $startdate, $enddate, $pk);
	
	if (isset($hashes->txhash)) {
		/*
		print_object('certificate hash: '.$hashes->certhash);
		print_object('startdate:        '.$startdate);
		print_object('enddate:          '.$enddate);
		print_object('tx hash:          '.$hashes->txhash);
		$cert = getCertificate($hashes->certhash);
		print_object($cert);
		*/
		return $hashes;
	}
	return false;
}

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
	}
	elseif (is_array($obj)) {
		//sort($obj); // TODO klären ob Arrays auch sortiert werden sollten
		$sortedobj = $obj;
	}
	elseif (is_string($obj)) {
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
		//print_object($match);
		$elements = explode('|', $match);
		//print_object($elements);
		$jsonobj = json_decode($certmetadatajson);
		foreach ($elements as $element) {
			foreach($jsonobj as $name => $value) {
				if ($name == $element) {
					if (is_array($value)) {
						$jsonobj = '<ul>';
						foreach ($value as $val) {
							$jsonobj .= '<li>'.$val.'</li>';
						}
						$jsonobj .= '</ul>';
					}
					else {
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
			//print_object($jsonobj);
			$html = str_replace('{'.$match.'}', $jsonobj, $html);
		}
		catch (Exception $e) {
			//print_object($e);
			/*
			echo $matches[0];
			return false;
			print_object($jsonobj);
			echo $html;
			*/
		}
	}
	
	return $html;
}

function generate_certmetadata($cm, $user) {
	global $CONTEXT_URL;
	$digitalcert = get_digitalcert($cm);
	
	$metadata = new stdClass();
	
	$metadata->badge = get_badge($cm, $digitalcert);
	if ($expiredate = get_expiredate($digitalcert->expiredate, $digitalcert->expireperiod)) {
		$metadata->expires = $expiredate;
	}
	$metadata->{'extensions:examinationRegulationsB4E'} = get_extension_examinationRegulationsB4E($digitalcert);
	$metadata->{'@context'} = $CONTEXT_URL->openbadges;
	$metadata->recipient = get_recipient();
	$metadata->{'extensions:recipientB4E'} = get_extension_recipientB4E($user);
	$metadata->{'extensions:examinationB4E'} = get_extension_examinationB4E($digitalcert);
	$metadata->type = 'Assertion';	
	
	return $metadata;
}

function get_issued_certificate($userid, $cmid, $ueid) {
	global $DB;
	if ($issued = $DB->get_record('ilddigitalcert_issued', array ('userid' => $userid, 'cmid' => $cmid, 'enrolmentid' => $ueid))) {
		return $issued->metadata;
	}
	return false;
}

function reissue_certificate($certmetadata, $userid, $cmid) {
	global $DB, $CFG;
	$courseid = $DB->get_field('course_modules', 'course', array('id' => $cmid));
	// Enrolmentid ermitteln
	$sql = 'SELECT ue.id FROM {user_enrolments} as ue, {enrol} e 
			 WHERE ue.enrolid = e.id 
			   and e.courseid = :courseid 
			   and ue.userid = :userid ';
	$params = array('courseid' => $courseid, 'userid' => $userid);
	$enrolmentid = 0;
	if ($enrolment = $DB->get_records_sql($sql, $params)) {
		if (count($enrolment) > 1) {
			print_error('to_many_enrolments', 'mod_ilddigitalcert', new moodle_url('/mod/ilddigitalcert/course/view.php', array('id' => $courseid)));
		}
		else {
			foreach($enrolment as $em) {
				$enrolmentid = $em->id;
			}
		}
	}
	else {
		print_error('not_enrolled', 'mod_ilddigitalcert', new moodle_url('/mod/ilddigitalcert/course/view.php', array('id' => $courseid)));
	}
	if ($issued = $DB->get_record('ilddigitalcert_issued', array ('userid' => $userid, 'cmid' => $cmid, 'enrolmentid' => $enrolmentid))) {
		// check if cert is already in blockchain. if so, print error
		if (isset($issued->certhash)) {
			print_error('already_in_blockchain', 'mod_ilddigitalcert', new moodle_url('/mod/ilddigitalcert/course/view.php', array('id' => $courseid)));
		}

		$issued->name = $certmetadata->badge->name;
		$issued->timemodified = time();
		
		$certmetadata->id = $CFG->wwwroot.'/mod/ilddigitalcert/view.php?issuedid='.$issued->id;
		$certmetadata->issuedOn = date('c', $issued->timemodified);
		$certmetadata->{'extensions:assertionreferenceB4E'} = get_extension_assertionreferenceB4E($issued->id);
		
		// assertionpageB4E enthält das komplette html, das aus dem Template + kompletten Metadaten erstellt wird
		$json = json_encode($certmetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);	
		$certmetadata->{'extensions:assertionpageB4E'} = get_extension_assertionpageB4E($cmid, $json);
		// deswegen muss das json anschließend mit html erneut erzeugt werden
		$json = json_encode($certmetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		
		$issued->metadata = $json;

		$DB->update_record('ilddigitalcert_issued', $issued);
		return true;
	}
	else {
		print_error('certificate_not_found', 'mod_ilddigitalcert', new moodle_url('/mod/ilddigitalcert/course/view.php', array('id' => $courseid)));
	}
}

function issue_certificate($certmetadata, $userid, $cmid) {
	global $DB, $CFG, $SITE;

	$courseid = $DB->get_field('course_modules', 'course', array('id' => $cmid));

	// Enrolmentid ermitteln
	$sql = 'SELECT ue.id FROM {user_enrolments} as ue, {enrol} e 
			 WHERE ue.enrolid = e.id 
			   and e.courseid = :courseid 
			   and ue.userid = :userid ';
	$params = array('courseid' => $courseid, 'userid' => $userid);

	$enrolmentid = 0;
	if ($enrolment = $DB->get_records_sql($sql, $params)) {
		if (count($enrolment) > 1) {
			print_error('to_many_enrolments', 'mod_ilddigitalcert', new moodle_url('/mod/ilddigitalcert/course/view.php', array('id' => $courseid)));
		}
		else {
			foreach($enrolment as $em) {
				$enrolmentid = $em->id;
			}
		}
	}
	else {
		print_error('not_enrolled', 'mod_ilddigitalcert', new moodle_url('/mod/ilddigitalcert/course/view.php', array('id' => $courseid)));
	}
	if ($issued = $DB->get_record('ilddigitalcert_issued', array ('userid' => $userid, 'cmid' => $cmid, 'enrolmentid' => $enrolmentid))) {
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
	
	$certmetadata->id = $CFG->wwwroot.'/mod/ilddigitalcert/view.php?issuedid='.$issuedid;
	$certmetadata->issuedOn = date('c', $issued->timemodified);
	$certmetadata->{'extensions:assertionreferenceB4E'} = get_extension_assertionreferenceB4E($issuedid);
	
	// assertionpageB4E enthält das komplette html, das aus dem Template + kompletten Metadaten erstellt wird
	$json = json_encode($certmetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);	
	$certmetadata->{'extensions:assertionpageB4E'} = get_extension_assertionpageB4E($cmid, $json);
	// deswegen muss das json anschließend mit html erneut erzeugt werden
	$json = json_encode($certmetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	
	$issued->metadata = $json;
	
	$DB->update_record('ilddigitalcert_issued', $issued);
	// email to user
	if ($user = $DB->get_record('user', array('id' => $userid))) {
		$from_user = core_user::get_support_user();
		$fullname = explode(' ', get_string('modulenameplural', 'mod_ilddigitalcert'));
		$from_user->firstname = $fullname[0];
		$from_user->lastname = $fullname[1];
		$subject = get_string('subject_new_certificate', 'mod_ilddigitalcert');
		$a = new stdClass();
		$a->fullname = $user->firstname.' '.$user->lastname;
		$a->url = $CFG->wwwroot.'/mod/ilddigitalcert/view.php?id='.$cmid;
		$a->from = $SITE->fullname;
		$message = get_string('message_new_certificate', 'mod_ilddigitalcert', $a);
		$message_html = get_string('message_new_certificate_html', 'mod_ilddigitalcert', $a);
		email_to_user($user, $from_user, $subject, $message, $message_html);
	}
	
	return $json;
}

function get_extension_assertionreferenceB4E($issuedid) {
	// Eindeutiger Referenzwert, der von einer Zertifizierungsstelle für ein Zertifikat vergeben wird
	/*
		"extensions:assertionreferenceB4E": {
			"assertionreference": "13616",
			"@context": "https://myotis.fit.fraunhofer.de/blockchain/pub/bscw.cgi/d4972/context.json",
			"type": [
			  "Extension",
			  "AssertionReferenceB4E"
			]
		 }
	*/
	global $CFG, $CONTEXT_URL;
	$extension = new stdClass();
	
	$extension->assertionreference = $CFG->wwwroot.'/mod/ilddigitalcert/view.php?issuedid='.$issuedid;
	$extension->{'@context'} = $CONTEXT_URL->assertionreferenceB4E;
	$extension->type = array('Extension', 'AssertionReferenceB4E');
	
	return $extension;
}

function get_extension_examinationB4E($digitalcert) {
	global $CONTEXT_URL;
	$extension = new stdClass();
	
	$extension->{'@context'} = $CONTEXT_URL->examinationB4E;
	$extension->type = array('Extension', 'ExaminationB4E');
	if ($digitalcert->examination_start > 0) {
		$extension->startdate = date('c', $digitalcert->examination_start);
	}
	if ($digitalcert->examination_end > 0) {
		$extension->enddate =  date('c', $digitalcert->examination_end);
	}
	if ($digitalcert->examination_place != '') {
		$extension->place = $digitalcert->examination_place;
	}
	
	return $extension;
}

function get_extension_recipientB4E($user) {
	global $CONTEXT_URL;
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
	$extension->{'@context'} = $CONTEXT_URL->recipientB4E;
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

function get_extension_examinationRegulationsB4E($digitalcert) {
	global $CONTEXT_URL;
	$extension = new stdClass();
	$extension->title = $digitalcert->examination_regulations;
	$extension->regulationsid = $digitalcert->examination_regulations_id;
	$extension->url = $digitalcert->examination_regulations_url;
	$extension->{'@context'} = $CONTEXT_URL->examinationRegulationsB4E;
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
	// expertise
	$lines = preg_split( "/[\r\n]+/", $digitalcert->expertise);
	$digitalcert->expertise = $lines;
	// tags
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

function get_extension_verifyB4E($hash) {
	global $CFG, $CONTEXT_URL;
	$verification = new stdClass();
	// TODO alternative url aus settings holen
	$verification->verifyaddress = $CFG->wwwroot.'/mod/ilddigitalcert/verify.php?hash='.$hash;
	$verification->type = array('Extension', 'VerifyB4E');
	$verification->assertionhash = 'sha256$'.substr($hash, 2);
	$verification->{'@context'} = $CONTEXT_URL->verifyB4E;
	return $verification;
}

function get_badge($cm, $digitalcert) {
	global $CONTEXT_URL;
	$badge = new stdClass();
	
	$badge->description = $digitalcert->description;
	$badge->name = $digitalcert->name;
	$badge->{'extensions:badgeexpertiseB4E'} = get_extension_badgeexpertiseB4E($digitalcert->expertise);
	$badge->issuer = get_issuer($digitalcert->issuer);
	$badge->{'@context'} = $CONTEXT_URL->openbadges;
	$badge->type = 'BadgeClass';
	$badge->{'extensions:badgetemplateB4E'} = get_extension_badgetemplateB4E();
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
		// $file is an instance of stored_file
		//echo $f->get_filename();
		$stored_file = $fs->get_file($context->id,
											  'mod_ilddigitalcert',
											  'content',
											  0,
											  $file->get_filepath(),
											  $file->get_filename());
		if ($file->get_filename() != '.') {
			break;
		}
	}
	if (isset($file)) {
		$content = $file->get_content();
	}
	else {
		return '';
	}
	// TODO: Bild Datei wird beim Anlegen des Zertifikats nicht gespeichert. erst beim erneuten Bearbeiten!
	$img_base64 = 'data:'.$stored_file->get_mimetype().';base64,'.base64_encode($content);
	//echo $img_base64;die();
	/*						  
	if (isset($stored_file)) {
		send_stored_file($stored_file, null, 0, false);
	}
	*/

	return $img_base64;
}

function get_issuerimage_base64($issuerid) {
	$context = context_system::instance();

	$fs = get_file_storage();
	$files = $fs->get_area_files($context->id, 'mod_ilddigitalcert', 'issuer', $issuerid);
	foreach ($files as $file) {
		if ($file->get_filename() != '.') {
			$stored_file = $fs->get_file($context->id,
										'mod_ilddigitalcert',
										'issuer',
										$issuerid,
										$file->get_filepath(),
										$file->get_filename());
			break;
		}
	}
	$content = $file->get_content();
	$img_base64 = 'data:'.$stored_file->get_mimetype().';base64,'.base64_encode($content);
	return $img_base64;
}

function get_extension_badgetemplateB4E() {
	global $CONTEXT_URL;
	$extension = new stdClass();
	
	$extension->{'@context'} = $CONTEXT_URL->badgetemplateB4E;
	$extension->type = array('Extension', 'BadgeTemplateB4E');
	
	return $extension;
}

function get_extension_badgeexpertiseB4E($expertise) {
	global $CONTEXT_URL;
	$extension = new stdClass();
	$extension->{'@context'} = $CONTEXT_URL->badgeexpertiseB4E;
	$extension->type = array('Extension', 'BadgeExpertiseB4E');
	$extension->expertise = $expertise;
	return $extension;
}

function get_issuer($issuerid) {
	global $DB, $CONTEXT_URL, $CFG;
	$issuerrecord = $DB->get_record('ilddigitalcert_issuer', array('id' => $issuerid));
	
	$issuer = new stdClass();
	$issuer->description = $issuerrecord->description;
	$issuer->{'extensions:addressB4E'} = get_extension_addressB4E($issuerrecord);
	$issuer->email = $issuerrecord->email;
	$issuer->name = $issuerrecord->name;
	$issuer->url = $issuerrecord->url;
	$issuer->{'@context'} = $CONTEXT_URL->openbadges;
	$issuer->type = 'Issuer';
	$issuer->id = $CFG->wwwroot.'/mod/ilddigitalcert/edit_issuers.php?action=edit&id='.$issuerrecord->id; // $issuerrecord->issuerid
	$issuer->image = get_issuerimage_base64($issuerid);
	
	return $issuer;
}

function get_extension_institutionTokenILD($token) {
	global $CONTEXT_URL;
	$extension = new stdClass();
	$extension->{'@context'} = $CONTEXT_URL->institutionTokenILD;
	$extension->type = array('Extension', 'InstitutionTokenILD');
	$extension->institutionToken = $token;
	return $extension;
}

function get_extension_assertionpageB4E($cmid, $certmetadatajson) {
	global $DB, $CONTEXT_URL;
	$cm = $DB->get_record('course_modules', array('id' => $cmid));
	$extension = new stdClass();
	$extension->{'@context'} = $CONTEXT_URL->assertionpageB4E;
	$extension->type = array('Extension', 'AssertionPageB4E');
	$extension->assertionpage = base64_encode(get_certificatehtml($cm->instance, $certmetadatajson));
	return $extension;
}

function get_extension_addressB4E($issuerrecord) {
	global $CONTEXT_URL;
	$address = new stdClass();
	
	$address->location = $issuerrecord->location;
	$address->zip = $issuerrecord->zip;
	$address->street = $issuerrecord->street;
	$address->{'@context'} = $CONTEXT_URL->addressB4E;
	$address->type = array('Extension', 'AddressB4E');
	if ($issuerrecord->pob != 0) {
		$address->pob = $issuerrecord->pob;
	}
	
	return $address;
}

function get_ipfs_hash($institutionProfile) {
	global $CFG;
	$institutionProfile = '1220'.substr($institutionProfile,2);
	// TODO evtl alternative zu perl finden
	// TODO ansonsten pfad zu perl in settings
	$file = '/usr/bin/perl '.$CFG->dirroot.'/mod/ilddigitalcert/perl/base58_encode.pl '.$institutionProfile;
	//$file = "/usr/bin/perl /opt/www/dev.oncampus.de/moodle3/oncampus/certchain/base58_encode.pl $institutionProfile";
	ob_start();
	passthru($file);
	$ipfs_hash = ob_get_contents();
	ob_end_clean();
	return $ipfs_hash;
}

function get_institution($ipfs_hash) {
	$institution = new stdClass();
	
	$ipfs_url = 'https://ipfs.io/ipfs/'.$ipfs_hash;

	try {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $ipfs_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5); //timeout in seconds
		//Also don't forget to enlarge time execution of php script self:
		//set_time_limit(0); // to infinity for example

		//curl_setopt($ch, CURLOPT_USERAGENT, 'MyBot/1.0 (http://www.mysite.com/)');
		
		$json_result = curl_exec($ch);

		if ($json_result === false) {
			//print_object('1: '.curl_error($ch));
			// Operation timed out after 10011 milliseconds with 0 out of -1 bytes received
		}
		elseif(($statuscode=curl_getinfo($ch, CURLINFO_HTTP_CODE)) == 200){
			// all fine
			// json decode?
			#$data = json_decode($data,true);
			//print_object('3: ok');
		}else{
			$error = 'could not reach ...! HTTP-Statuscode: '.$statuscode;
			//print_object('2: '.$error);
		}

		curl_close($ch);
	} catch (Exception $e) {
		print_object($e);
	}

	//$json_result1 = file_get_contents($ipfs_url);
	//print_object($json_result1);
	
	//print_object($json_result);
	$institution = json_decode($json_result);
	
	return $institution;
}

function add_certifier($userid, $user_address, $admin_pk) {
	require_once('web3lib.php');
	global $DB;
	// Check if user already exists in user_preferences
	//if (get_user_preferences('mod_ilddigitalcert_certifier', false, $userid)) {
	if ($user_pref = $DB->get_record('user_preferences', array('name' => 'mod_ilddigitalcert_certifier', 'userid' => $userid))) {
		#print_object($user_pref);
		if (strpos($user_pref->value, 'not_registered_pk') === false) {
			//print_object('error');die();
			print_error('user_is_already_certifier', 'mod_ilddigitalcert', new moodle_url('/mod/ilddigitalcert/edit_certifiers.php'));
		}
	}
	// Check if $user_address already exists in user_preferences
	if ($user_pref = $DB->get_record('user_preferences', array('name' => 'mod_ilddigitalcert_certifier', 'value' => $user_address))) {
		print_error('address_is_already_used', 'mod_ilddigitalcert', new moodle_url('/mod/ilddigitalcert/edit_certifiers.php'));
	}
	// in Blockchain hinzufügen
	//print_object($admin_pk);die();
	add_certifier_to_blockchain($user_address, $admin_pk);
	//print_object('add_certifier_to_blockchain... ok');
	// wenn erfolgreich (isAccreditedCertifier)
	$start = time();
	$ac = false;
	while (1) {
		$now = time();
		if ($ac = is_accredited_certifier($user_address)) {
			break;
		}
		if ($now - $start > 30) {
			break;
		}
	}
	if ($ac) {
		// userpref anlegen
		set_user_preference('mod_ilddigitalcert_certifier', $user_address, $userid);
		return true;
	}
	else {
		print_error('error_while_adding_certifier_to_blockchain', 'mod_ilddigitalcert', new moodle_url('/mod/ilddigitalcert/edit_certifiers.php'));
	}
}

// delete certifier
function remove_Certifier($userprefid, $admin_pk) {
	global $DB;
	if ($user_pref = $DB->get_record('user_preferences', array('id' => $userprefid))) {
		$user_address = $user_pref->value;
		if (is_accredited_certifier($user_address)) {
			// Zertifizierer aus Blockchain entfernen
			remove_certifier_from_blockchain($user_address, $admin_pk);
			$start = time();
			$ac = true;
			while (1) {
				$now = time();
				$ac = is_accredited_certifier($user_address);
				if (!$ac) {
					break;
				}
				if ($now - $start > 30) {
					break;
				}
			}
			if ($ac) {
				print_error('error_while_removing_certifier_from_blockchain', 'mod_ilddigitalcert', new moodle_url('/mod/ilddigitalcert/edit_certifiers.php'));
			}
			else {
				// und wenn erfolgreich
				// aus userpref löschen
				unset_user_preference('mod_ilddigitalcert_certifier', $user_pref->userid);
				// TODO Email an Ex-Zertifizierer
				return true;
			}
		}
		else {
			unset_user_preference('mod_ilddigitalcert_certifier', $user_pref->userid);
			print_error('certifier_already_removed_from_blockchain', 'mod_ilddigitalcert', new moodle_url('/mod/ilddigitalcert/edit_certifiers.php'));
		}
	}
	else {
		print_error('certifier_already_removed_from_blockchain', 'mod_ilddigitalcert', new moodle_url('/mod/ilddigitalcert/edit_certifiers.php'));
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

function get_issuer_name_from_address($institution_address) {
	global $DB;
	if ($issuers = $DB->get_records('ilddigitalcert_issuer', array('address' => $institution_address))) {
		if (count($issuers) > 1) {
			print_error('found_address_more_than_one_times', 'mod_ilddigitalcert');
		}
		//print_object($issuers);
		foreach ($issuers as $issuer) {
			return $issuer->name;
		}
	}
	print_error('address_not_found', 'mod_ilddigitalcert');
}

function reset_user($courseid, $userid) {
	global $DB;

	$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
#/*
	$completion = new completion_info($course);
    if (!$completion->is_enabled()) {
        return;
	}
	
    $DB->delete_records_select('course_modules_completion',
		'coursemoduleid IN (SELECT id 
								FROM mdl_course_modules 
								WHERE course=?) 
			AND userid=?',
		array($courseid, $userid));
	$DB->delete_records('course_completions', array('course' => $courseid, 'userid' => $userid));
	$DB->delete_records('course_completion_crit_compl', array('course' => $courseid, 'userid' => $userid));

	$dbman = $DB->get_manager();
	
    if ($dbman->table_exists('scorm_scoes_track')) {
        $DB->delete_records_select('scorm_scoes_track',
			'scormid IN (SELECT id FROM mdl_scorm WHERE course=?) 
				AND userid=?',
            array($courseid, $userid));
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
#*/
}

function display_metadata($metadata) {
	//echo '<div>';
	if (is_array($metadata)) {
		echo '<ul>';
		foreach ($metadata as $value) {
			echo '<li>';
			echo $value;
			echo '</li>';
		}
		echo '</ul>';
	}
	elseif (is_object($metadata)){
		echo '<ul>';
		foreach ($metadata as $key => $value) {
			if ($key != '@context' and $key != 'type' and $value != '' and $key != 'extensions:assertionpageB4E') {
				if ($key == 'image') {
					echo '<br />';
					echo '<img src="'.$value.'" style="max-width:150px; max-height:150px;">';
				}
				else {
					if ($key == 'issuedOn' or $key == 'date' or $key == 'expires' or $key == 'certificationdate') {
						$value = date('d.m.Y', strtotime($value));
					}
					if ($key == 'startdate') { // TODO
						$value = date('d.m.Y', strtotime($value));
					}
					if ($key == 'enddate') { // TODO
						$value = date('d.m.Y', strtotime($value));
					}
					if (has_content($value)) {
						echo '<li>';
						echo '<b>'.$key.'</b>: ';
						display_metadata($value);
						echo '</li>';
					}
				}
			}
		}
		echo '</ul>';
	}
	else {
		echo $metadata;
	}
}

function has_content($metadata_obj) {
	if (is_string($metadata_obj) and $metadata_obj != '') {
		return true;
	}
	foreach ($metadata_obj as $key => $value) {
		if ($key != '@context' and $key != 'type' and $value != '' and $key != 'extensions:assertionpageB4E') {
			return true;
		}
	}
	return false;
}

function download_file($fileid) {
	global $DB, $CFG;
	
	if ($file = $DB->get_record('files', array('id' => $fileid))) {
		
		$file_storage = get_file_storage();

		$stored_file = $file_storage->get_file($file->contextid,
											  $file->component,
											  $file->filearea,
											  $file->itemid,
											  $file->filepath,
											  $file->filename);
								  
		send_stored_file($stored_file, null, 0, false);
	}
	
}

function debug_email($to, $message, $debug_object = NULL) {
	global $USER;
	$subject = 'debug';
	$from = $USER;
	if (isset($debug_object)) {
		ob_start();
		print_object($debug_object);
		$message .= ob_get_contents();
		ob_end_clean();
	}
	email_to_user($to, $from, $subject, $message, $message);
}