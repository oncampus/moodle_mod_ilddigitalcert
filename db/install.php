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
 * post installation hook for adding entry in customusermenu.
 *
 * @package    mod_ilddigitalcert
 * @copyright  2020 Jan Rieger <jan.rieger@th-luebeck.de> ILD Technische Hochschule Lübeck
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Post installation procedure
 */
function xmldb_ilddigitalcert_install() {
    $result = true;
    
    $old_menu = $CFG->customusermenuitems;
    $new_menu = $old_menu .= "\nmodulenameplural,mod_ilddigitalcert|/mod/ilddigitalcert/overview.php|grades";
    set_config('customusermenuitems', $new_menu);

    return $result;
}