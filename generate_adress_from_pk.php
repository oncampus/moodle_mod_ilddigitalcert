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
 * Shows site for generating a private key and associated blockchain address
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('web3lib.php');

require_login();

$pk = optional_param('pk', '', PARAM_ALPHANUM);

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/ilddigitalcert/generate_adress_from_pk.php');
$PAGE->set_title(get_string('pluginname', 'mod_ilddigitalcert'));
$PAGE->set_heading(get_string('pluginname', 'mod_ilddigitalcert'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('generate_adr_from_pk', 'mod_ilddigitalcert'));

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
echo '<p>';
echo $prefix.'Private Key: ';
echo $pk;
echo '</p><p>';
echo 'address: ';
echo get_address_from_pk($pk);
echo '</p>';
echo $OUTPUT->footer();
