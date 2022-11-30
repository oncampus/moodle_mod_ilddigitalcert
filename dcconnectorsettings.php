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
 * Interface for connector (Digital Campus) settings.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2021 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();

$context = context_system::instance();

if (has_capability('moodle/site:config', $context)) {
    $PAGE->set_context($context);
    $PAGE->set_url('/mod/ilddigitalcert/dcconnectorsettings.php');
    $PAGE->set_title(get_string('dcconnectorsettings', 'mod_ilddigitalcert'));
    $PAGE->set_heading(get_string('dcconnectorsettings', 'mod_ilddigitalcert'));

    // Inform moodle which menu entry currently is active!
    admin_externalpage_setup('ilddigitalcert_dcconnectorsettings');

    $url = new moodle_url('/mod/ilddigitalcert/dcconnectorsettings.php');
    $mform = new mod_ilddigitalcert\output\form\dcconnectorsettings_form($url);

    if ($mform->is_cancelled()) {
        redirect($url);
    } else if ($fromform = $mform->get_data()) {
        // get_config($plugin, $name) set_config($name, $value, $plugin)
        set_config('dchost', $fromform->dchost, 'mod_ilddigitalcert');
        set_config('dcxapikey', $fromform->dcxapikey, 'mod_ilddigitalcert');
        set_config('dcconnectoraddress', $fromform->dcconnectoraddress, 'mod_ilddigitalcert');
        redirect($url);
    }
    echo $OUTPUT->header();

    $toform = new stdClass();
    $toform->dchost = get_config('mod_ilddigitalcert', 'dchost');
    $toform->dcxapikey = get_config('mod_ilddigitalcert', 'dcxapikey');
    $toform->dcconnectoraddress = get_config('mod_ilddigitalcert', 'dcconnectoraddress');
    $mform->set_data($toform);
    $mform->display();

    echo $OUTPUT->footer();
} else {
    redirect($CFG->wwwroot);
}
