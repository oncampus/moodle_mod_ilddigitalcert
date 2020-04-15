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
 * Prints an instance of mod_ilddigitalcert.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <support@oncampus.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once('locallib.php');
require_once($CFG->libdir.'/tablelib.php');

// Course_module ID, or
$id = optional_param('id', 0, PARAM_INT);
$view = optional_param('view', 'html', PARAM_RAW);
$issuedid = optional_param('issuedid', 0, PARAM_INT);
$download = optional_param('download', 'json', PARAM_RAW);
$ueid = optional_param('ueid', 0, PARAM_INT);


if ($id) {
    $cm             = get_coursemodule_from_id('ilddigitalcert', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('ilddigitalcert', array('id' => $cm->instance), '*', MUST_EXIST);
} 
else {
    print_error(get_string('missingidandcmid', 'mod_ilddigitalcert'));
}

require_login();
$modulecontext = context_module::instance($cm->id);

// wenn parameter $ueid aus overview.php übergeben ist kein kurslogin nötig um alte zertifikate auch zu sehen
if ($ueid == 0) {
	require_login($course, true, $cm);

	$PAGE->set_url('/mod/ilddigitalcert/view.php', array('id' => $cm->id));
	$PAGE->set_title(format_string($moduleinstance->name));
	$PAGE->set_heading(format_string($course->fullname));
	$PAGE->set_context($modulecontext);
}
else {
	$context = context_system::instance();

	$PAGE->set_url('/mod/ilddigitalcert/view.php', array('id' => $cm->id, 'ueid' => $ueid));
	$PAGE->set_pagelayout('admin');
	$PAGE->set_title(format_string($moduleinstance->name));
	$PAGE->set_heading(format_string($course->fullname));
	$PAGE->set_context($context);
}

if (isguestuser()) {
    redirect($CFG->wwwroot.'/login/');
}

$id = $cm->id;

// Zertifikat ansehen als Teacher/certifier
if ($issuedid > 0 and has_capability('moodle/grade:viewall', context_course::instance($course->id))) {
	$issued_certificate = $DB->get_record('ilddigitalcert_issued', array('id' => $issuedid));
	$certmetadatajson = $issued_certificate->metadata;

	$metadataobj = json_decode($certmetadatajson);
	$filename = $issued_certificate->name.'_'.
				$metadataobj->{'extensions:recipientB4E'}->givenname.'_'.
				$metadataobj->{'extensions:recipientB4E'}->surname.'_'.
				strtotime($metadataobj->issuedOn).'.bcrt';
	$filename = 'certificate.bcrt';
//echo $filename; die();
	if ($view == 'download') {
		$fs = get_file_storage();
		$fileinfo = array(
			'contextid' => $modulecontext->id, 		// ID of context
			'component' => 'mod_ilddigitalcert',    // usually = table name
			'filearea' => 'metadata',     			// usually = table name
			'itemid' => $issued_certificate->id,	// usually = ID of row in table
			'filepath' => '/',           			// any path beginning and ending in /
			'filename' => $filename); 		// any filename
		$file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
				$fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
		if ($file) {
			$file->delete();
		}

		// institution token / salt hinzufügen damit der Hash auch richtig berechnet werden kann
		$token = get_token($issued_certificate->institution_token);
		$metadata = json_decode($certmetadatajson);
		$metadata->{'extensions:institutionTokenILD'} = get_extension_institutionTokenILD($token);
		$certmetadatajson = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

		$fs->create_file_from_string($fileinfo, $certmetadatajson);
		//redirect($CFG->wwwroot.'/mod/ilddigitalcert/download.php?id='.$modulecontext->id.'&icid='.$issued_certificate->id.'&cmid='.$cm->id);
		
		$url = $CFG->wwwroot.'/mod/ilddigitalcert/download.php?id='.$modulecontext->id.'&icid='.$issued_certificate->id.'&cmid='.$cm->id.'&download='.$download;
		//die($url);
		redirect($url);
	}
	
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string('pluginname', 'mod_ilddigitalcert'));
	
	echo html_writer::link(new moodle_url('/mod/ilddigitalcert/view.php?id='.$id.'&issuedid='.$issuedid.'&view=html&ueid='.$ueid), get_string('html', 'mod_ilddigitalcert'));
	echo ' | ';
	echo html_writer::link(new moodle_url('/mod/ilddigitalcert/view.php?id='.$id.'&issuedid='.$issuedid.'&view=data&ueid='.$ueid), get_string('data', 'mod_ilddigitalcert'));
	if (isset($issued_certificate->txhash)) {		
		echo '<br />'.get_string('download').': ';
		echo html_writer::link(new moodle_url('/mod/ilddigitalcert/view.php?id='.$id.'&issuedid='.$issuedid.'&view=download&ueid='.$ueid), get_string('json', 'mod_ilddigitalcert'));
		$pdf = true; // TODO in die Settings
		if ($pdf) {
			echo ' | ';
			echo html_writer::link(new moodle_url('/mod/ilddigitalcert/view.php?id='.$id.'&issuedid='.$issuedid.'&view=download&download=pdf&ueid='.$ueid), get_string('pdf', 'mod_ilddigitalcert'));
		}
		
		
	}
	// TODO Zertifikat anzeigen
	if ($view == 'data') {
		$metadata = json_decode($certmetadatajson);
		echo '<div><p>';
		display_metadata($metadata);
		echo '</p></div>';
		//print_object($metadata);
		//echo '<pre>'.$certmetadatajson.'</pre>';
	}
	elseif ($view == 'html') {
		echo '<div id="zertifikat-page" style="border: 0px solid #bfbfbf;margin: 20px 0px;max-width: 800px;">';
		echo get_certificatehtml($cm->instance, $certmetadatajson);
		echo '</div>';
		
		if (isset($issued_certificate->txhash)) {
			// QR-Code anzeigen
			echo '<br />';
			echo '<h3>Zertifikat in der Blockchain überprüfen</h3>'; // TODO sprachpaket

			$salt = get_token($issued_certificate->institution_token);
			$metadata = json_decode($certmetadatajson);
			$metadata->{'extensions:institutionTokenILD'} = get_extension_institutionTokenILD($salt);
			$certmetadatajson = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

			$hash = calculate_hash($certmetadatajson);
			$url = $CFG->wwwroot.'/mod/ilddigitalcert/verify.php?hash='.$hash;
			$img = '<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl='.$url.'&choe=UTF-8" title="Zertifikat überprüfen" />';
			
			echo html_writer::link($url, $img);
		}
	}
	
	echo '<p>';
	echo html_writer::link($CFG->wwwroot.'/mod/ilddigitalcert/view.php?id='.$id.'&ueid='.$ueid, get_string('back'));
	echo '</p>';
	echo $OUTPUT->footer();
}
elseif (has_capability('moodle/grade:viewall', context_course::instance($course->id)) and $view == 'preview') {
// Vorschau anzeigen
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string('pluginname', 'mod_ilddigitalcert'));
	echo '<p>'.get_string('preview', 'mod_ilddigitalcert').' "'.$moduleinstance->name.'"</p>';
	echo '<div id="zertifikat-page" style="border: 0px solid #bfbfbf;margin: 20px 0px;max-width: 800px;">';
	echo get_certificatehtml($cm->instance, json_encode(generate_certmetadata($cm, $USER)));
	echo '</div>';
	echo '<p>'.html_writer::link($CFG->wwwroot.'/mod/ilddigitalcert/view.php?id='.$id.'&ueid='.$ueid, get_string('back')).'</p>';
	echo $OUTPUT->footer();
}
elseif (has_capability('moodle/grade:viewall', context_course::instance($course->id)) and $view != 'download') {
	// Zertifikatsübersicht als Teacher/certifier
	$PAGE->requires->css(new moodle_url($CFG->wwwroot.'/mod/ilddigitalcert/css/pk_form.css'));
	$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/mod/ilddigitalcert/js/pk_form.js'));
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string('pluginname', 'mod_ilddigitalcert'));
	// Vorschau-Link
	echo '<p>'.
		 html_writer::link($CFG->wwwroot.'/mod/ilddigitalcert/view.php?id='.$id.'&view=preview', get_string('preview', 'mod_ilddigitalcert').' "'.$moduleinstance->name.'"').
		 '</p>';

	echo '<div id="myModal" class="modal">
			<div class="modal-content">
				<div class="modal-header">
					<span class="close">&times;</span>
					<h2>'.get_string('sign_cert', 'mod_ilddigitalcert').'</h2>
				</div>
				<div class="modal-body">
					<p>&nbsp;</p>
					<p>'.get_string('sign_with_pk', 'mod_ilddigitalcert').'</p>
					<form method="post" action="'.new moodle_url($CFG->wwwroot.'/mod/ilddigitalcert/view.php?id='.$id.'&ueid='.$ueid).'">
						Private Key: <input class="pk-input" id="pk" type="text" name="pk" pattern="[A-Za-z0-9]{64}" required>
						<input id="issued" type="hidden" name="issued" value="-1">
						<input type="hidden" name="action" value="toblockchain"><br/><br/>
						<p style="text-align: center;">
							<button type="submit" name="id" value="'.$id.'">In Blockchain speichern</button>
						</p>
					</form>
				</div>
				<!--
				<div class="modal-footer">
					<h3>Footer</h3>
				</div>
				-->
			</div>
		</div>';
	
	echo '<h1>'.get_string('certificate_overview', 'mod_ilddigitalcert').' "'.$course->fullname.'"</h1>';
	
	$action = optional_param('action', '', PARAM_RAW);
	$issued = optional_param('issued', 0, PARAM_INT);
	$pk = optional_param('pk', '', PARAM_RAW); 
	$reissueid = optional_param('reissueid', 0, PARAM_INT);
	//print_object('action: '.$action);print_object('issued: '.$issued);print_object('pk: '.$pk);
	if ($action == 'toblockchain' and $issued > 0) {
		$issued_certificate = $DB->get_record('ilddigitalcert_issued', array('id' => $issued));
		
		#/*
		if (to_blockchain($issued_certificate, $USER, $pk)) {
			echo '<p style="color:#00ce0a;">Zertifikat erfolgreich in der Blockchain gespeichert und signiert!</p>'; // TODO ins Sprachpaket
		}
		else {
			echo '<p style="color:red;">Fehler beim Speichern in der Blockchain!</p>'; // TODO ins Sprachpaket
		}
		#*/
		//to_blockchain($issued_certificate, $USER);
	}
	elseif ($action == 'reissue' and $reissueid > 0) {
		//echo '*reissue* 0';
		if ($reissue = $DB->get_record('ilddigitalcert_issued', array('id' => $reissueid))) {
			//echo '*reissue* 1';
			if ($reissue_user = $DB->get_record('user', array('id' => $reissue->userid, 'confirmed' => 1, 'deleted' => 0))) {
				//echo '*reissue* 2';
				$certmetadata = generate_certmetadata($cm, $reissue_user);
				reissue_certificate($certmetadata, $reissue->userid, $cm->id);
			}
		}
	} 
	$search = optional_param('search', '', PARAM_ALPHA);
	$check_only_bc = optional_param('check_only_bc', '', PARAM_RAW);
	$check_only_nonbc = optional_param('check_only_nonbc', '', PARAM_RAW);
	$and = '';
	if ($check_only_bc == 'check_only_bc') {
		$and = ' AND idci.certhash is not null ';
		if ($search == '') {
			$search = '%';
		}
	}
	elseif ($check_only_nonbc == 'check_only_nonbc') {
		$and = ' AND idci.certhash is null ';
		if ($search == '') {
			$search = '%';
		}
	}
	if ($search != '') {
		// SELECT * FROM mdl_ilddigitalcert_issued idci, mdl_user u WHERE idci.courseid = 160 AND u.id = idci.userid AND (CONCAT(u.firstname, ' ', u.lastname) LIKE '%Muster%' OR idci.name LIKE '%Muster%' )
		$fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
		$sql = 'SELECT idci.* 
				  FROM {ilddigitalcert_issued} idci, {user} u 
				 WHERE idci.courseid = :courseid 
				   AND u.id = idci.userid 
				   AND ('.$DB->sql_like($fullname, ':search1', false, false).' 
					OR '.$DB->sql_like('idci.name', ':search2', false, false).') 
					'.$and;
		//print_object($sql);
		$params = array('courseid' => $course->id,
						'search1' => '%'.$search.'%', 
						'search2' => '%'.$search.'%');
		$issued_certificates = $DB->get_records_sql($sql, $params);
		$search = '';
	}
	else {
		$issued_certificates = $DB->get_records('ilddigitalcert_issued', array('courseid' => $course->id));
	}
	
	$table = new flexible_table('MODULE_TABLE');
	$table->define_columns(array('icon', 
								 'name',
								 'recipient',
								 'issuedon',
								 'actions'));
	$table->define_headers(array(get_string('status'),
								 get_string('title', 'mod_ilddigitalcert'),
								 get_string('recipient', 'mod_ilddigitalcert'),
								 get_string('startdate', 'mod_ilddigitalcert'),
								 get_string('actions')));
	$table->define_baseurl($CFG->wwwroot.'/mod/ilddigitalcert/view.php?id='.$id.'&view='.$view.'&action='.$action.'&issued='.$issued.'&ueid='.$ueid);
	$table->set_attribute('class', 'admintable generaltable');
	$table->sortable(false, 'name', SORT_ASC);
	$table->setup();	
	
	foreach ($issued_certificates as $issued_certificate) {
		$data = array();
		$icon = '<img height="32px" title="'.get_string('pluginname', 'mod_ilddigitalcert').'" src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/blockchain-certificate.svg">';
		if (isset($issued_certificate->txhash)) {
			$icon .= '<img height="32px" title="'.get_string('registered_and_signed', 'mod_ilddigitalcert').'" src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/blockchain-block.svg">';
		}
		$data[] = $icon;
		$user = $DB->get_record_sql('select id, firstname, lastname from {user} where id = :id ', array('id' => $issued_certificate->userid));
		
		// TODO Zertifikat anzeigen 
		$data[] = html_writer::link(new moodle_url('/mod/ilddigitalcert/view.php?id='.$issued_certificate->cmid.'&issuedid='.$issued_certificate->id.'&ueid='.$ueid), $issued_certificate->name);
		
		$data[] = html_writer::link(new moodle_url('/user/view.php?id='.$user->id.'&course='.$course->id.'&ueid='.$ueid), $user->firstname.' '.$user->lastname);
		$data[] = date('d.m.Y - H:i', $issued_certificate->timecreated);
		// TODO Zertifikat neu ausstellen
		// Zertifikat in Blockchain speichern
		if (!isset($issued_certificate->txhash)) {
			$data[] = '<button class="myBtn" value="'.$issued_certificate->id.'">'.get_string('toblockchain', 'mod_ilddigitalcert').'</button> '.
					  /*
					  '<form method="post" action="'.new moodle_url($CFG->wwwroot.'/mod/ilddigitalcert/view.php').'"> 
						<input type="hidden" name="action" value="reissue">
						<input type="hidden" name="id" value="'.$issued_certificate->cmid.'">
						<input type="hidden" name="reissueid" value="'.$issued_certificate->id.'">
						<button type="submit">'.get_string('reissue', 'mod_ilddigitalcert').'</button>
					   </form>';
					   */
					  html_writer::link(new moodle_url('/mod/ilddigitalcert/view.php?id='.$issued_certificate->cmid.'&reissueid='.$issued_certificate->id.'&action=reissue'), '<img alt="reissue certificate" title="reissue certificate" src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/refresh_grey_24x24.png">');
			
		}
		else {
			// TODO check revoked
			// TODO if not revoked: revoke certificate
			$data[] = '';
			// TODO if revoked: unrevoke certificate
		}
		$table->add_data($data);
	}
	// Suchfeld
	echo '<p>';
	echo '<form action="'.new moodle_url('/mod/ilddigitalcert/view.php?id='.$id.'&ueid='.$ueid).'" class="searchform">';
	echo '<div>';
	echo '<input type="hidden" name="id" value="'.$id.'" />';
	echo '<input type="hidden" name="ueid" value="'.$ueid.'" />';
	echo '<input type="checkbox" id="check_only_bc" name="check_only_bc" value="check_only_bc">'.get_string('only_blockchain', 'mod_ilddigitalcert').'<br />';
	echo '<input type="checkbox" id="check_only_nonbc" name="check_only_nonbc" value="check_only_nonbc">'.get_string('only_nonblockchain', 'mod_ilddigitalcert').'<br />';
	echo '<input type="text" id="search" name="search" value="'.s($search).'" />&nbsp;';
	echo '<input type="submit" value="'.get_string('search').'" style="margin-top: 9px;height: 27px;padding-top: 2px;"/>';
	echo '&nbsp;'.html_writer::link(new moodle_url('/mod/ilddigitalcert/view.php?id='.$id.'&ueid='.$ueid), get_string('cancel'));
	echo '</div>';
	echo '</form>';
	echo '</p>';
	
	echo '<p>'.get_string('modulenameplural', 'mod_ilddigitalcert').': '.count($issued_certificates).'</p>';
	
	$table->print_html();
	
	echo $OUTPUT->footer();
}
else {
	// TODO unterscheiden ob $ueid (dann neue Funtktion get_issued_certificate) oder aktuelles enrolment

	// Zertifikat ansehen/erstellen als student
	$certmetadata = generate_certmetadata($cm, $USER);

	if ($ueid == 0) {
		$certmetadatajson = issue_certificate($certmetadata, $USER->id, $cm->id);
	}
	else {
		$certmetadatajson = get_issued_certificate($USER->id, $cm->id, $ueid);
	}
	if (!$certmetadatajson) {
		print_error('found_no_issued_certificate', 'mod_ilddigitalcert', new moodle_url('/mod/ilddigitalcert/course/view.php', array('id' => $courseid, 'ueid' => $ueid)));
	}
	$issued_certificate = $DB->get_record('ilddigitalcert_issued', array('userid' => $USER->id, 'cmid' => $cm->id));
	
	// TODO: nur wenn in BC gespeichert
	
	$bc = true;

	 
	if ($view == 'download' and $bc) {
		//echo '<br />TODO'; // TODO: json datei speichern wenn noch nicht gescheichen
		
		
		$fs = get_file_storage();
		#/*
		$metadataobj = json_decode($certmetadatajson);
		$filename = $issued_certificate->name.'_'.
					$metadataobj->{'extensions:recipientB4E'}->givenname.'_'.
					$metadataobj->{'extensions:recipientB4E'}->surname.'_'.
					strtotime($metadataobj->issuedOn).'.bcrt';
					//echo $filename; die();
		$filename = 'certificate.bcrt';
		// Prepare file record object
		$fileinfo = array(
			'contextid' => $modulecontext->id, 		// ID of context
			'component' => 'mod_ilddigitalcert',    // usually = table name
			'filearea' => 'metadata',     			// usually = table name
			'itemid' => $issued_certificate->id,	// usually = ID of row in table
			'filepath' => '/',           			// any path beginning and ending in /
			'filename' => $filename); 		// any filename
			
		// Get file
		$file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
				$fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
		 
		// Delete it if it exists
		if ($file) {
			$file->delete();
		}

		// institution token / salt hinzufügen damit der Hash auch richtig berechnet werden kann
		$token = get_token($issued_certificate->institution_token);
		$metadata = json_decode($certmetadatajson);
		$metadata->{'extensions:institutionTokenILD'} = get_extension_institutionTokenILD($token);
		$certmetadatajson = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

		// Create file 
		$fs->create_file_from_string($fileinfo, $certmetadatajson);
		#*/
		
		//print_object($certmetadatajson);die();
		// TODO prüfen was passiert, wenn sich der Inhalt geändert hat
		//die($download);
		$url = $CFG->wwwroot.'/mod/ilddigitalcert/download.php?id='.$modulecontext->id.'&icid='.$issued_certificate->id.'&cmid='.$cm->id.'&download='.$download;
		//die($url);
		redirect($url);
	}
	
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string('pluginname', 'mod_ilddigitalcert'));
	//echo '*';
	
	//TODO nur anzeigen wenn schon in blockchain. ansonsten:
	if (!isset($issued_certificate->txhash)) {
		\core\notification::info(get_string('cert_waiting_for_registration', 'mod_ilddigitalcert'));
	}
	echo html_writer::link(new moodle_url('/mod/ilddigitalcert/view.php?id='.$id.'&view=html&ueid='.$ueid), get_string('html', 'mod_ilddigitalcert'));
	echo ' | ';
	echo html_writer::link(new moodle_url('/mod/ilddigitalcert/view.php?id='.$id.'&view=data&ueid='.$ueid), get_string('data', 'mod_ilddigitalcert'));
	if (isset($issued_certificate->txhash)) {
		echo '<br />'.get_string('download').': ';
		// echo html_writer::link(new moodle_url('/mod/ilddigitalcert/view.php?id='.$id.'&view=download&ueid='.$ueid), get_string('json', 'mod_ilddigitalcert'));
		$pdf = true; // TODO in die Settings
		if ($pdf) {
			//echo ' | ';
			echo html_writer::link(new moodle_url('/mod/ilddigitalcert/view.php?id='.$id.'&view=download&download=pdf&ueid='.$ueid), get_string('pdf', 'mod_ilddigitalcert'));
		}
	}
	
	if ($view == 'data') {
		$metadata = json_decode($certmetadatajson);
		echo '<div><p>';
		display_metadata($metadata);
		echo '</p></div>';
		//echo '<pre>'.$certmetadatajson.'</pre>';
	}
	elseif ($view == 'html') {
		echo '<div id="zertifikat-page" style="border: 0px solid #bfbfbf;margin: 20px 0px;max-width: 800px;">';
		echo get_certificatehtml($cm->instance, $certmetadatajson);
		echo '</div>';
		
		if (isset($issued_certificate->txhash)) {
			// QR-Code anzeigen
			echo '<br />';
			echo '<h3>Zertifikat in der Blockchain überprüfen</h3>';

			$salt = get_token($issued_certificate->institution_token);
			$metadata = json_decode($certmetadatajson);
			$metadata->{'extensions:institutionTokenILD'} = get_extension_institutionTokenILD($salt);
			$certmetadatajson = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

			$hash = calculate_hash($certmetadatajson);
			
			$url = $CFG->wwwroot.'/mod/ilddigitalcert/verify.php?hash='.$hash;
			$img = '<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl='.$url.'&choe=UTF-8" title="Zertifikat überprüfen" />';
			
			echo html_writer::link($url, $img);
		}
	}
	
	echo $OUTPUT->footer();
}