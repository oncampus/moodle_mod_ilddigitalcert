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
 * Interface for adding and removing certifiers to/from blockchain.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
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

if (!has_capability('moodle/site:config', $context)) { // TODO write new cap.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('pluginname', 'mod_ilddigitalcert'));
    echo 'Permission denied!';
    echo $OUTPUT->footer();
    die();
}

$options = array('accesscontext' => $context);
$userselector = new core_role_check_users_selector('certifier', $options);
$userselector->set_rows(10);

$selecteduser = $userselector->get_selected_user();

// Add certifier to blockchain.
if (isset($selecteduser)) {
    $adminpk = optional_param('institution_pk', '', PARAM_RAW);
    $useraddress = optional_param('certifier_address', '', PARAM_RAW);

    $success = false;

    $touser = $DB->get_record('user', array('id' => $selecteduser->id));
    $fromuser = $DB->get_record('user', array('id' => $USER->id));
    $msgstrings = new stdClass();
    $msgstrings->fullname = $touser->firstname.' '.$touser->lastname;
    $msgstrings->url = $CFG->wwwroot;
    $msg = 'new_certifier_message';
    $msghtml = 'new_certifier_message_html';
    if ($useraddress != '') {

        $success = add_certifier($selecteduser->id, $useraddress, $adminpk);
        $msg = 'new_certifier_message';
        $msghtml = 'new_certifier_message_html';
    } else {
        // Send email with link for generating private key.
        // Generate entry without blockchain address in list.
        // Add userpref.
        $token = random_bytes(32);
        $token = bin2hex($token);
        $tokenlink = $CFG->wwwroot.'/mod/ilddigitalcert/generate_pk.php?t='.$token;
        set_user_preference('mod_ilddigitalcert_certifier', 'not_registered_token_'.$token, $selecteduser->id);
        $msgstrings->token_link = $tokenlink;
        $msgstrings->from = $SITE->fullname;
        $subject = get_string('subject_generate_pk', 'mod_ilddigitalcert');
        $message = get_string('message_generate_pk', 'mod_ilddigitalcert', $msgstrings);
        $messagehtml = get_string('message_html_generate_pk', 'mod_ilddigitalcert', $msgstrings);
        email_to_user($touser, $fromuser, $subject, $message, $messagehtml);
    }
    // If success: email to certifier.
    if ($success) {
        // Address of certification authority.
        $adminaddress = get_institution_from_certifier($useraddress);
        $msgstrings->institution = get_issuer_name_from_address($adminaddress);
        $msgstrings->from = $msgstrings->institution;
        $message = get_string($msg, 'mod_ilddigitalcert', $msgstrings);
        $messagehtml = get_string($msghtml, 'mod_ilddigitalcert', $msgstrings);
        $subject = get_string('new_certifier_subject', 'mod_ilddigitalcert');
        email_to_user($touser, $fromuser, $subject, $message, $messagehtml);
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
echo '<p>&nbsp;</p>
      <p id="radiogroup1" style="padding-left: 17px;">
        <input checked type="radio" id="radio_generate_pk" name="generate_pk" value="pk" style="margin: 0 4px 0 -17px;">
        <span>'.get_string('generate_pk', 'mod_ilddigitalcert').'</span><br /><br />
        <input type="radio" id="radio_address" name="generate_pk" value="pk" style="margin: 0 4px 0 -17px;">'.
        get_string('use_address', 'mod_ilddigitalcert').'
      </p>';
// Input certifier Address.
echo '<p style="padding-left: 17px;">
        <label for="certifier_address">'.
          get_string('certifier_address', 'mod_ilddigitalcert').':<br />'.
        '</label>
        <input id="certifierAddress" type="text" name="certifier_address" pattern="[A-Za-z0-9]{42}" value="" disabled/>
      </p>';
echo '<p id="warning_select_user" style="color:red;display:none">'.get_string('select_user', 'mod_ilddigitalcert').'</p>';
echo '<p id="pk_button_p">
        <button type="button" id="pk_button">'.get_string('add_certifier', 'mod_ilddigitalcert').'</button>
        <input type="reset" id="pk_button_reset" value="' . get_string('cancel') . '" ' .'class="btn btn-primary"/>
      </p>
      ';
// Input Institution PK.
echo '<p id="pk_input" style="display:none;">
        <label style="color:red;" for="institution_pk">'.
          get_string('institution_pk', 'mod_ilddigitalcert').
        '</label> <input id="institutionPK" type="text" name="institution_pk" pattern="[A-Za-z0-9]{64}" required>
      </p>';

// Submit button and the end of the form.
echo '<p id="chooseusersubmit"  style="display:none;">
        <input id="pk_submit" type="submit" value="'.
          get_string('add_certifier', 'mod_ilddigitalcert').'" '.'class="btn btn-primary"/>
        <input id="pk_submit_reset" type="reset" value="'.get_string('cancel').'" '.'class="btn btn-primary"/>
      </p>';
echo '</form>';
echo $OUTPUT->box_end();

// If action == delete_certifier...
$action = optional_param('action', '', PARAM_RAW);
$pk = optional_param('pk', '', PARAM_RAW);
$userprefid = optional_param('userpref', 0, PARAM_INT);
$pkadd = optional_param('pkAdd', '', PARAM_RAW);
$userprefaddid = optional_param('userprefAdd', 0, PARAM_INT);
if ($action == 'delete_certifier' and $pk != '' and $userprefid > 0) {
    remove_certifier($userprefid, $pk);
} else if ($action == 'add_certifier' and $pkadd != '' and $userprefaddid > 0) {
    $userpref = $DB->get_record('user_preferences', array('id' => $userprefaddid));
    $userprefuseraddress = substr($userpref->value, 18);
    add_certifier($userpref->userid, $userprefuseraddress, $pkadd);
}// Add certifier (with confirmation dialog).

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

// Bestätigungsformular zum entfernen des Zertifizierers.
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

// Tabelle anzeigen.
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

$certifiers = $DB->get_records('user_preferences', array('name' => 'mod_ilddigitalcert_certifier'));

foreach ($certifiers as $certifier) {
    $data = array();
    $user = $DB->get_record_sql('select id, firstname, lastname, email from {user} where id = :id ',
      array('id' => $certifier->userid));
    $data[] = $user->firstname.' '.$user->lastname;
    $data[] = $user->email;
    if (strpos($certifier->value, 'not_registered_token') !== false) {
        $value = get_string('waiting_for_pk_generation', 'mod_ilddigitalcert');
        // TODO ablehnen Button (ohne email).
        $button = '';
        // Backup:'<button class="registerBtn" value="'.$certifier->id.'">'.
        // Backup: get_string('register_certifier', 'mod_ilddigitalcert').'</button>';//!
    } else if (strpos($certifier->value, 'not_registered_pk') !== false) {
        $value = get_string('waiting_for_registration', 'mod_ilddigitalcert');
        // TODO ablehnen Button (mit email).
        $button = '<button class="registerBtn" value="'.$certifier->id.'">'.
          get_string('add_certifier', 'mod_ilddigitalcert').'</button>';
    } else {
        $value = $certifier->value;
        $button = '<button class="deleteBtn" value="'.$certifier->id.'">'.
          get_string('delete_certifier', 'mod_ilddigitalcert').'</button>';
    }
    $data[] = $value;
    $data[] = $button;

    $table->add_data($data);
}

$table->print_html();

echo $OUTPUT->footer();
