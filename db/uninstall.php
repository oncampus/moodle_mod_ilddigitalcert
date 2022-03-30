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
 * Post installation hook for removing an entry in custom user menu.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2021, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Post installation procedure.
 *
 * Remove the digital certificate menu item from the custom user menu.
 */
function xmldb_ilddigitalcert_uninstall() {
    global $CFG;
    $result = true;

    $menuitem = "\nmodulenameplural,mod_ilddigitalcert|/mod/ilddigitalcert/overview.php|grades";
    $menu = $CFG->customusermenuitems;
    $menu = str_replace($menuitem, "", $menu);
    set_config('customusermenuitems', $menu);

    return $result;
}
