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
 * @copyright  2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Post installation procedure.
 */
function xmldb_ilddigitalcert_install() {
    global $CFG;
    $result = true;

    $newitem = "\nmodulenameplural,mod_ilddigitalcert|/mod/ilddigitalcert/overview.php|grades";
    $menu = $CFG->customusermenuitems;

    // Remove any old motbot menu items, if there are any.
    $menu = str_replace($newitem, "", $menu);

    // Add the motbot menu item.
    $menu = $menu .= $newitem;
    set_config('customusermenuitems', $menu);

    return $result;
}
