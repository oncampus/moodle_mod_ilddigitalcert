<?php

require_once(__DIR__.'/../../config.php');
require_once('edit_issuers_form.php');
require_once($CFG->libdir.'/tablelib.php');
require_once('locallib.php');
require_once('web3lib.php');
require_once($CFG->libdir.'/adminlib.php');

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/ilddigitalcert/edit_certifiers.php');
$PAGE->set_title(get_string('pluginname', 'mod_ilddigitalcert'));
$PAGE->set_heading(get_string('pluginname', 'mod_ilddigitalcert'));

require_login();
// Inform moodle which menu entry currently is active!
admin_externalpage_setup('ilddigitalcert_edit_certifiers');

if (!has_capability('moodle/site:config', $context)) { // TODO neue Cap erfinden
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('pluginname', 'mod_ilddigitalcert'));
    echo 'Permission denied!';
    echo $OUTPUT->footer();
    die();
}

$options = array('accesscontext' => $context);
$userselector = new core_role_check_users_selector('certifier', $options);
//$userselector = new user_selector_base('certifier');
$userselector->set_rows(10);

$selected_user = $userselector->get_selected_user();

// Zertifizierer zur Blockchain hinzufügen
if (isset($selected_user)) {
  //print_object($selected_user);
  //print_object($userid);
  $admin_pk = optional_param('institution_pk', '', PARAM_RAW);
  $user_address = optional_param('certifier_address', '', PARAM_RAW);
  
  $success = false;

  $to_user = $DB->get_record('user', array('id' => $selected_user->id));
  $from_user = $DB->get_record('user', array('id' => $USER->id));
  $msg_strings = new stdClass();
  $msg_strings->fullname = $to_user->firstname.' '.$to_user->lastname;
  $msg_strings->url = $CFG->wwwroot;
  $msg = 'new_certifier_message';
  $msg_html = 'new_certifier_message_html';
  //print_object($user_address);
  if ($user_address != '') {
    
    $success = add_certifier($selected_user->id, $user_address, $admin_pk);
    $msg = 'new_certifier_message';
		$msg_html = 'new_certifier_message_html';
  }
  else { // Link zum generieren des Private Key per Mail versenden
    // Eintrag ohne Blockchainadresse in der Liste erzeugen
    // userpref anlegen
    $token = random_bytes(32);
    $token = bin2hex($token);
    $token_link = $CFG->wwwroot.'/mod/ilddigitalcert/generate_pk.php?t='.$token;
    set_user_preference('mod_ilddigitalcert_certifier', 'not_registered_token_'.$token, $selected_user->id);
    $msg_strings->token_link = $token_link;
    $msg_strings->from = $SITE->fullname;
    $subject = get_string('subject_generate_pk', 'mod_ilddigitalcert');
    $message = get_string('message_generate_pk', 'mod_ilddigitalcert', $msg_strings);
    $message_html = get_string('message_html_generate_pk', 'mod_ilddigitalcert', $msg_strings);
    email_to_user($to_user, $from_user, $subject, $message, $message_html);
  }
  // wenn erfolgreich email an certifier
  if ($success) {
    //$admin_address = get_address_from_pk($admin_pk); // Adresse der Zertifizierungsstelle
    $admin_address = get_institution_from_certifier($user_address); // Adresse der Zertifizierungsstelle
    $msg_strings->institution = get_issuer_name_from_address($admin_address);
    $msg_strings->from = $msg_strings->institution;
    $message = get_string($msg, 'mod_ilddigitalcert', $msg_strings);
		$message_html = get_string($msg_html, 'mod_ilddigitalcert', $msg_strings);
    //print_object($msg_strings);
		$subject = get_string('new_certifier_subject', 'mod_ilddigitalcert');
		email_to_user($to_user, $from_user, $subject, $message, $message_html);
  }
}

$PAGE->requires->css(new moodle_url($CFG->wwwroot.'/mod/ilddigitalcert/css/pk_form.css'));
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/mod/ilddigitalcert/js/edit_certifiers.js'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('edit_certifiers', 'mod_ilddigitalcert'));
echo $OUTPUT->box_start('generalbox boxwidthnormal boxaligncenter', 'chooseuser');
echo '<form method="post" action="'.$PAGE->url.'" >';

// User selector.
$userselector->display();
// style="color:lightgrey;"
echo '<p>&nbsp;</p>
      <p id="radiogroup1" style="padding-left: 17px;">
        <input checked type="radio" id="radio_generate_pk" name="generate_pk" value="pk" style="margin: 0 4px 0 -17px;"><span>'.get_string('generate_pk', 'mod_ilddigitalcert').'</span><br /><br />
        <input type="radio" id="radio_address" name="generate_pk" value="pk" style="margin: 0 4px 0 -17px;">'.get_string('use_address', 'mod_ilddigitalcert').'
      </p>';
// Input certifier Address
echo '<p style="padding-left: 17px;">
        <label for="certifier_address">'.
          get_string('certifier_address', 'mod_ilddigitalcert').':<br />'.
          //$new_pk. // TODO remove!!!
        '</label>
        <input id="certifierAddress" type="text" name="certifier_address" pattern="[A-Za-z0-9]{42}" value="" disabled/>
      </p>';
echo '<p id="warning_select_user" style="color:red;display:none">'.get_string('select_user', 'mod_ilddigitalcert').'</p>';
echo '<p id="pk_button_p">
        <button type="button" id="pk_button">'.get_string('add_certifier', 'mod_ilddigitalcert').'</button>
        <input type="reset" id="pk_button_reset" value="' . get_string('cancel') . '" ' .'class="btn btn-primary"/>
      </p>
      ';
// Input Institution PK
echo '<p id="pk_input" style="display:none;">
        <label style="color:red;" for="institution_pk">'.get_string('institution_pk', 'mod_ilddigitalcert').'</label> <input id="institutionPK" type="text" name="institution_pk" pattern="[A-Za-z0-9]{64}" required>
      </p>';

// Submit button and the end of the form.
echo '<p id="chooseusersubmit"  style="display:none;">
        <input id="pk_submit" type="submit" value="' . get_string('add_certifier', 'mod_ilddigitalcert') . '" ' .'class="btn btn-primary"/>
        <input id="pk_submit_reset" type="reset" value="' . get_string('cancel') . '" ' .'class="btn btn-primary"/>
      </p>';
echo '</form>';
echo $OUTPUT->box_end();

// if action == delete_certifier
$action = optional_param('action', '', PARAM_RAW);
$pk = optional_param('pk', '', PARAM_RAW);
$userprefid = optional_param('userpref', 0, PARAM_INT);
$pkadd = optional_param('pkAdd', '', PARAM_RAW);
$userprefaddid = optional_param('userprefAdd', 0, PARAM_INT);
if ($action == 'delete_certifier' and $pk != '' and $userprefid > 0) {
  remove_Certifier($userprefid, $pk);
}
elseif ($action == 'add_certifier' and $pkadd != '' and $userprefaddid > 0) {
  #print_object('action: '.$action);
  #print_object('pk: '.$pkadd);
  #print_object('userprefid: '.$userprefaddid);
  $userpref = $DB->get_record('user_preferences', array('id' => $userprefaddid));
  $userpref_useraddress = substr($userpref->value, 18);
  //print_object('add_certifier('.$userpref->userid.', '.$userpref_useraddress.', '.$pkadd.')');
  add_certifier($userpref->userid, $userpref_useraddress, $pkadd);
}// Zertifizierer hinzufügen mit Bestätigungsformular

echo '<div id="myModalAddCertifier" class="modal">
			<div class="modal-content">
				<div class="modal-header">
					<span class="close">&times;</span>
					<h2>'.get_string('add_certifier', 'mod_ilddigitalcert').'</h2>
				</div>
				<div class="modal-body">
					<p>&nbsp;</p>
					<p>'.get_string('sign_add_certifier_with_pk', 'mod_ilddigitalcert').'</p>
					<form method="post" action="'.new moodle_url($CFG->wwwroot.'/mod/ilddigitalcert/edit_certifiers.php').'">
						Private Key: <input class="pk-input" id="pkAdd" type="text" name="pkAdd" pattern="[A-Za-z0-9]{64}" required>
						<input id="userprefAdd" type="hidden" name="userprefAdd" value="-1">
						<input type="hidden" name="action" value="add_certifier"><br/><br/>
						<p style="text-align: center;">
							<button type="submit" >'.get_string('add_certifier', 'mod_ilddigitalcert').'</button>
						</p>
					</form>
				</div>
			</div>
    </div>';

// Bestätigungsformular zum entfernen des Zertifizierers
echo '<div id="myModal" class="modal">
			<div class="modal-content">
				<div class="modal-header">
					<span class="close">&times;</span>
					<h2>'.get_string('delete_certifier', 'mod_ilddigitalcert').'</h2>
				</div>
				<div class="modal-body">
					<p>&nbsp;</p>
					<p>'.get_string('sign_delete_certifier_with_pk', 'mod_ilddigitalcert').'</p>
					<form method="post" action="'.new moodle_url($CFG->wwwroot.'/mod/ilddigitalcert/edit_certifiers.php').'">
						Private Key: <input class="pk-input" id="pk" type="text" name="pk" pattern="[A-Za-z0-9]{64}" required>
						<input id="userpref" type="hidden" name="userpref" value="-1">
						<input type="hidden" name="action" value="delete_certifier"><br/><br/>
						<p style="text-align: center;">
							<button type="submit" >'.get_string('delete_certifier', 'mod_ilddigitalcert').'</button>
						</p>
					</form>
				</div>
			</div>
    </div>';

// Tabelle anzeigen
$table = new flexible_table('MODULE_TABLE');
$table->define_columns(array('name', 
                              'email',
                              'address',
                              'actions'));
$table->define_headers(array(get_string('fullname'),
                              get_string('email'),
                              get_string('certifier_address', 'mod_ilddigitalcert'),
                              get_string('actions')));
$table->define_baseurl($CFG->wwwroot.'/mod/ilddigitalcert/edit_certifiers.php');
$table->set_attribute('class', 'admintable generaltable');
$table->sortable(false, 'name', SORT_ASC);
$table->setup();

//$certifiers = get_user_preferences('mod_ilddigitalcert_certifier');
$certifiers = $DB->get_records('user_preferences', array('name' => 'mod_ilddigitalcert_certifier'));
//print_object($certifiers);
foreach ($certifiers as $certifier) {
  $data = array();
  $user = $DB->get_record_sql('select id, firstname, lastname, email from {user} where id = :id ', array('id' => $certifier->userid));
  $data[] = $user->firstname.' '.$user->lastname;
  $data[] = $user->email;
  if (strpos($certifier->value, 'not_registered_token') !== false) {
    $value = get_string('waiting_for_pk_generation', 'mod_ilddigitalcert');
    // TODO ablehnen Button (ohne email)
    $button = '';//'<button class="registerBtn" value="'.$certifier->id.'">'.get_string('register_certifier', 'mod_ilddigitalcert').'</button>';
  }
  elseif (strpos($certifier->value, 'not_registered_pk') !== false) {
    $value = get_string('waiting_for_registration', 'mod_ilddigitalcert');
    // TODO ablehnen Button (mit email)
    $button = '<button class="registerBtn" value="'.$certifier->id.'">'.get_string('add_certifier', 'mod_ilddigitalcert').'</button>';
  }
  else {
    $value = $certifier->value;
    $button = '<button class="deleteBtn" value="'.$certifier->id.'">'.get_string('delete_certifier', 'mod_ilddigitalcert').'</button>';
  }
  $data[] = $value;
  $data[] = $button;

  $table->add_data($data);
}

$table->print_html();

echo $OUTPUT->footer();
