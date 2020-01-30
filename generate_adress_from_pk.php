<?php

require_once('../../config.php');
require_once('web3lib.php');

$pk = optional_param('pk', '', PARAM_ALPHANUM);

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/ilddigitalcert/generate_adress_from_pk.php');
$PAGE->set_title(get_string('pluginname', 'mod_ilddigitalcert'));
$PAGE->set_heading(get_string('pluginname', 'mod_ilddigitalcert'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('generate_adr_from_pk', 'mod_ilddigitalcert'));

//echo 'Generate blockchain adress from private key';

echo '<br /><br />';
echo '<form method="post" action="'.$PAGE->url.'" >';
echo '<input class="pk-input" id="pk" type="text" name="pk" pattern="[A-Za-z0-9]{64}">';
echo '<button type="submit" >'.get_string('ok').'</button>';

$prefix = '';
if ($pk == '') {
    $prefix = 'Random ';
    $bytes = random_bytes(32);
    $pk = strtoupper(bin2hex($bytes));
}
echo '<br /><br />';
echo $prefix.'Private Key: ';
print_object($pk);
//echo '<br /><br />';
echo 'address: ';
print_object(get_address_from_pk($pk));
echo $OUTPUT->footer();