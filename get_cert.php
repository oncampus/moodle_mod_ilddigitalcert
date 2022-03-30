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
 * Gets the certificate metadate when hash and token are correct
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');

require_login();

$hash = optional_param('hash', '', PARAM_ALPHANUM);
$token = optional_param('token', '', PARAM_ALPHANUM);

// TODO Token darf nicht in der DB liegen!
if ($cert = $DB->get_record('ilddigitalcert_issued', array('certhash' => $hash, 'institution_token' => $token))) {
    echo $cert->metadata;
} else {
    echo 'error';
}
