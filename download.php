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
 * This script is producing a downloadable file
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');

require_login();

// TODO check capabilities.
$icid = optional_param('icid', 0, PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$download = optional_param('download', 'json', PARAM_RAW);

if ($icid != 0) {
    download_json($icid, $download);

} else {
    redirect(new moodle_url('/mod/ilddigitalcert/view.php', array('id' => $cmid)));
}
