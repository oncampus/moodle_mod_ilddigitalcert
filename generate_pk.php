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
 * Shows site for generating a private key from link in email
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// TODO: cronjob must check age of token and eventually delete it.
// TODO: Add max token-age to settings.

require_once(__DIR__.'/../../config.php');
require_once('locallib.php');
require_once('web3lib.php');

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/ilddigitalcert/generate_pk.php');
$PAGE->set_title(get_string('pluginname', 'mod_ilddigitalcert'));
$PAGE->set_heading(get_string('pluginname', 'mod_ilddigitalcert'));

require_login();

$token = optional_param('t', '', PARAM_RAW);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('subject_generate_pk', 'mod_ilddigitalcert'));

$userpref = get_user_preferences('mod_ilddigitalcert_certifier', '', $USER);
// Compare $token with token from $userpref.
if (isset($userpref) and strpos($userpref, 'not_registered_token') !== false) {
    $preftoken = substr($userpref, 21, 64);
    if ($preftoken == $token) {
        $bytes = random_bytes(32);
        $newpk = strtoupper(bin2hex($bytes));
        $newaddress = get_address_from_pk($newpk);
        // TODO: check if address already exists in Blockchain.
        // TODO: Testen! Zeile wurde als Bugfix entfernt // $newpk = strtoupper(bin2hex($bytes)); //.
        $a = new stdClass();
        $a->pk = $newpk;
        echo get_string('new_pk_generated', 'mod_ilddigitalcert', $a);
        // Change entry in user_preferences.
        set_user_preference('mod_ilddigitalcert_certifier', 'not_registered_pk_'.$newaddress, $USER->id);
        // TODO: email to institution (email address from settings).
    } else {
        $a->fullname = $USER->firstname.' '.$USER->lastname;
        echo get_string('no_pref_found', 'mod_ilddigitalcert', $a); // TODO Text ändern (token ungültig).
    }
} else { // Z.B. bei Gastlogin.
    $a = new stdClass();
    $a->fullname = $USER->firstname.' '.$USER->lastname;
    echo get_string('no_pref_found', 'mod_ilddigitalcert', $a); // TODO Text ändern (token ungültig).
}

echo $OUTPUT->footer();
