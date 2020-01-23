<?php

// TODO: cronjob sollte prüfen wie alt der token bereits ist und diesen evtl. löschen
// TODO: max token-Alter in die Settings

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

$user_pref = get_user_preferences('mod_ilddigitalcert_certifier', '', $USER);
// compare $token mit token aus $user_pref
if (isset($user_pref) and strpos($user_pref, 'not_registered_token') !== false) {
    $pref_token = substr($user_pref, 21, 64);
    //print_object('pref:  '.$pref_token);
    //print_object('param: '.$token);
    if ($pref_token == $token) {
        $bytes = random_bytes(32);
        $new_pk = strtoupper(bin2hex($bytes));
        $new_address = get_address_from_pk($new_pk);
        // TODO: check if address already exists in Blockchain
        $new_pk = strtoupper(bin2hex($bytes));
        $a = new stdClass();
        $a->pk = $new_pk;
        echo get_string('new_pk_generated', 'mod_ilddigitalcert', $a);
        // change entry in user_preferences
        set_user_preference('mod_ilddigitalcert_certifier', 'not_registered_pk_'.$new_address, $USER->id);
        // TODO: email to institution (email address from settings)
    }
    else {
        $a->fullname = $USER->firstname.' '.$USER->lastname;
        echo get_string('no_pref_found', 'mod_ilddigitalcert', $a); // TODO Text ändern (token ungültig)
    }
}
else { // z.B. bei Gastlogin
    $a = new stdClass();
    $a->fullname = $USER->firstname.' '.$USER->lastname;
    echo get_string('no_pref_found', 'mod_ilddigitalcert', $a); // TODO Text ändern (token ungültig)
}

echo $OUTPUT->footer();