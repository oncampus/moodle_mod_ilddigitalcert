<?php

require_once(__DIR__.'/../../config.php');

$hash = optional_param('hash', '', PARAM_ALPHANUM);
$token = optional_param('token', '', PARAM_ALPHANUM);

// TODO Token darf nicht in der DB liegen!
if ($cert = $DB->get_record('ilddigitalcert_issued', array('certhash' => $hash, 'institution_token' => $token))) {
    //print_object($cert);
    echo $cert->metadata;
}
else {
    echo 'error';
}