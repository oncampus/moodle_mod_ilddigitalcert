<?php

require_once('../../config.php');
require_once('web3lib.php');

echo 'Generate blockchain adress from private key';

$pk = optional_param('pk', '', PARAM_ALPHANUM);

echo ' "'.$pk.'"';
echo '<br /><br />';
echo 'adress: '.get_address_from_pk($pk);

