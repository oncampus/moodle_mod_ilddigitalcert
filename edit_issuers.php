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
 * Interface for adding, editing and removing certification authorities.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();

$context = context_system::instance();

if (has_capability('moodle/site:config', $context)) {
    $PAGE->set_context($context);
    $PAGE->set_url('/mod/ilddigitalcert/edit_issuers.php');
    $PAGE->set_title(get_string('edit_issuers', 'mod_ilddigitalcert'));
    $PAGE->set_heading(get_string('edit_issuers', 'mod_ilddigitalcert'));

    // Inform moodle which menu entry currently is active!
    admin_externalpage_setup('ilddigitalcert_edit_issuers');

    $id = optional_param('id', 0, PARAM_INT);
    $action = optional_param('action', '', PARAM_RAW);

    // Projekte.
    $url = new moodle_url('/mod/ilddigitalcert/edit_issuers.php');

    $mform = new mod_ilddigitalcert\output\form\edit_issuers_form($url.'?id='.$id, array('issuerid' => $id));

    if ($mform->is_cancelled()) {
        redirect($url);
    } else if ($fromform = $mform->get_data()) {
        require_once('locallib.php');
        $issuer = new stdClass();
        $issuer->name = $fromform->issuername_label;
        $issuer->description = $fromform->issuerdescription;
        $issuer->email = $fromform->issueremail;
        $issuer->url = $fromform->issuerurl;
        $issuer->location = $fromform->issuerlocation;
        $issuer->zip = $fromform->issuerzip;
        $issuer->street = $fromform->issuerstreet;
        $issuer->pob = $fromform->issuerpob;
        $issuer->address = $fromform->issueraddress;

        if (isset($fromform->issuerid) and $fromform->issuerid > 0) {
            $issuer->id = $fromform->issuerid;
            $DB->update_record('ilddigitalcert_issuer', $issuer);
        } else {
            $issuer->id = $DB->insert_record('ilddigitalcert_issuer', $issuer);
        }
        if (isset($issuer->id) and $issuer->id > 0) {
            file_save_draft_area_files($fromform->issuerimage,
                                       $context->id,
                                       'mod_ilddigitalcert',
                                       'issuer',
                                       $issuer->id,
                                       array('maxfiles' => 1));
        }
        redirect($url);
    }

    echo $OUTPUT->header();
    echo html_writer::tag('h1', get_string('overview', 'mod_ilddigitalcert'));

    $issuers = $DB->get_records('ilddigitalcert_issuer');
    if (count($issuers) > 0) {
        echo '<ul>';
        foreach ($issuers as $issuer) {
            echo '<li>'.$issuer->name.' '.
                    html_writer::link(
                        new moodle_url('/mod/ilddigitalcert/edit_issuers.php?id='.$issuer->id.'&action=edit'),
                        get_string('edit')).' | '.html_writer::link(
                            new moodle_url('/mod/ilddigitalcert/edit_issuers.php?id='.$issuer->id.'&action=delete'),
                            get_string('delete')).
                '</li>';
        }
        echo '</ul>';
    }

    if ($action == 'edit' and $id != 0) {
        $result = $DB->get_record('ilddigitalcert_issuer', array('id' => $id));
        $toform = new stdClass();
        $toform->issuername_label = $result->name;
        $toform->issuerdescription = $result->description;
        $toform->issueremail = $result->email;
        $toform->issuerurl = $result->url;
        $toform->issuerlocation = $result->location;
        $toform->issuerzip = $result->zip;
        $toform->issuerstreet = $result->street;
        $toform->issuerpob = $result->pob;
        $toform->issueraddress = $result->address;

        $draftitemid = file_get_submitted_draft_itemid('issuerimage');
        file_prepare_draft_area($draftitemid, $context->id, 'mod_ilddigitalcert', 'issuer', $id, array('maxfiles' => 1));
        $toform->issuerimage = $draftitemid;

        $mform->set_data($toform);
    }

    $mform->display();

    echo $OUTPUT->footer();
} else {
    redirect($CFG->wwwroot);
}
