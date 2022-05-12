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
 * Prints an instance of mod_ilddigitalcert.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once('locallib.php');

use mod_ilddigitalcert\bcert\certificate;

$id = required_param('id', PARAM_INT);
$view = optional_param('view', 'html', PARAM_RAW);
$issuedid = optional_param('issuedid', 0, PARAM_INT);
$download = optional_param('download', 'json', PARAM_RAW);
$ueid = optional_param('ueid', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('ilddigitalcert', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('ilddigitalcert', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    throw new moodle_exception(get_string('missingidandcmid', 'mod_ilddigitalcert'));
}

require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

if (isguestuser()) {
    redirect($CFG->wwwroot . '/login/');
}

// Wenn parameter $ueid aus overview.php übergeben ist kein kurslogin nötig um alte zertifikate auch zu sehen.
if ($ueid == 0) {
    $PAGE->set_url('/mod/ilddigitalcert/view.php', array('id' => $cm->id));
} else {
    $PAGE->set_url('/mod/ilddigitalcert/view.php', array('id' => $cm->id, 'ueid' => $ueid));
    $PAGE->set_pagelayout('admin');
}

$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Zertifikat ansehen als Teacher/certifier.
if ($issuedid > 0 and has_capability('moodle/grade:viewall', context_course::instance($course->id))) {
    $issuedcertificate = $DB->get_record('ilddigitalcert_issued', array('id' => $issuedid));
    $metacertificate = certificate::from_ob($issuedcertificate->metadata);

    if ($view == 'download') {
        create_certificate_files($issuedcertificate, $metacertificate);

        $url = new moodle_url(
            '/mod/ilddigitalcert/download.php',
            array('icid' => $issuedcertificate->id, 'cmid' => $cm->id, 'download' => $download)
        );
        redirect($url);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('pluginname', 'mod_ilddigitalcert'));

    echo html_writer::link(
        new moodle_url(
            '/mod/ilddigitalcert/view.php',
            array('id' => $id, 'issuedid' => $issuedid, 'view' => 'html', 'ueid' => $ueid)
        ),
        get_string('html', 'mod_ilddigitalcert')
    );
    echo ' | ';
    echo html_writer::link(
        new moodle_url(
            '/mod/ilddigitalcert/view.php',
            array('id' => $id, 'issuedid' => $issuedid, 'view' => 'data', 'ueid' => $ueid)
        ),
        get_string('data', 'mod_ilddigitalcert')
    );
    if (isset($issuedcertificate->txhash)) {
        // Create certificate files, that can be downloaded.
        create_certificate_files($issuedcertificate, $metacertificate);

        echo '<br />' . get_string('download') . ': ';
        echo html_writer::link(
            new moodle_url(
                '/mod/ilddigitalcert/view.php',
                array('id' => $id, 'issuedid' => $issuedid, 'view' => 'download', 'ueid' => $ueid)
            ),
            get_string('json', 'mod_ilddigitalcert'));

        if (isset($issuedcertificate->edci)) {
            echo ' | ';
            echo html_writer::link(
                new moodle_url(
                    '/mod/ilddigitalcert/view.php',
                    array('id' => $id, 'issuedid' => $issuedid, 'view' => 'download', 'download' => 'edci', 'ueid' => $ueid)
                ),
                get_string('edci', 'mod_ilddigitalcert'));
        }

        $pdf = true; // TODO in die Settings!
        if ($pdf) {
            echo ' | ';
            echo html_writer::link(
                new moodle_url(
                    '/mod/ilddigitalcert/view.php',
                    array('id' => $id, 'issuedid' => $issuedid, 'view' => 'download', 'download' => 'pdf', 'ueid' => $ueid)
                ),
                get_string('pdf', 'mod_ilddigitalcert')
            );
        }
        if (
            '0' != get_config('mod_ilddigitalcert', 'dchost') and
            '0' != get_config('mod_ilddigitalcert', 'dcxapikey')
        ) {
            echo ' | ';
            echo html_writer::link(
                new moodle_url(
                    '/mod/ilddigitalcert/send_to_wallet.php',
                    array('id' => $cm->id)
                ),
                get_string('send_to_wallet', 'mod_ilddigitalcert')
            );
        }
    }
    // TODO Zertifikat anzeigen!
    if ($view == 'data') {
        echo '<div><p>';
        display_metadata($metacertificate->get_ob(false));
        echo '</p></div>';
    } else if ($view == 'html') {
        echo '<div id="zertifikat-page" style="border: 0px solid #bfbfbf;margin: 20px 0px;max-width: 800px;">';
        echo get_certificatehtml($cm->instance, $metacertificate->get_ob());
        echo '</div>';

        if (isset($issuedcertificate->txhash)) {
            // QR-Code anzeigen.
            echo '<br />';
            echo '<h3>' . get_string('verify_authenticity', 'mod_ilddigitalcert') . '</h3>';

            $salt = get_token($issuedcertificate->institution_token);
            $hash = $metacertificate->get_ob_hash($salt);
            $url = new moodle_url('/mod/ilddigitalcert/verify.php', array('hash' => $hash));
            $img = '<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . $url . '&choe=UTF-8"
                title="' . get_string('verify_authenticity', 'mod_ilddigitalcert') . '" />';
            echo html_writer::link($url, $img);
        }
    }

    echo '<p>';
    echo html_writer::link($CFG->wwwroot . '/mod/ilddigitalcert/view.php?id=' . $id . '&ueid=' . $ueid, get_string('back'));
    echo '</p>';
    echo $OUTPUT->footer();
} else if (has_capability('moodle/grade:viewall', context_course::instance($course->id)) and $view == 'preview') {
    // Vorschau anzeigen.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('pluginname', 'mod_ilddigitalcert'));
    echo '<p>' . get_string('preview', 'mod_ilddigitalcert') . ' "' . $moduleinstance->name . '"</p>';
    echo '<div id="zertifikat-page" style="border: 0px solid #bfbfbf;margin: 20px 0px;max-width: 800px;">';
    echo get_certificatehtml($cm->instance, certificate::new($cm, $USER)->get_ob());
    echo '</div>';
    echo '<p>' . html_writer::link(
        new moodle_url('/mod/ilddigitalcert/view.php', array('id' => $id, 'ueid' => $ueid)),
        get_string('back')
    ) . '</p>';
    echo $OUTPUT->footer();
} else if (has_capability('moodle/grade:viewall', context_course::instance($course->id)) and $view = 'html') {
    // TODO elseif (has_capability('moodle/grade:viewall', context_course::instance($course->id)) and $view == 'issue_teacher')//!
    // Zertifikatsübersicht als Teacher/certifier.
    redirect(new moodle_url('/mod/ilddigitalcert/overview.php', array('id' => $cm->id, 'ueid' => $ueid)));
} else {
    // TODO unterscheiden ob $ueid (dann neue Funtktion get_issued_certificate) oder aktuelles enrolment!

    // View Certificate as student.

    if ($ueid == 0) {
        $certmetadatajson = issue_certificate(certificate::new($cm, $USER), $cm);
    } else {
        $certmetadatajson = get_issued_certificate($USER->id, $cm->id, $ueid);
    }
    if (!$certmetadatajson) {
        throw new moodle_exception(
            'found_no_issued_certificate',
            'mod_ilddigitalcert',
            new moodle_url(
                '/mod/ilddigitalcert/course/view.php',
                array('id' => $courseid, 'ueid' => $ueid)
            )
        );
    }

    $issuedcertificate = $DB->get_record('ilddigitalcert_issued', array('userid' => $USER->id, 'cmid' => $cm->id));
    $metacertificate = certificate::from_ob($certmetadatajson);

    // Download only if certificcate is registerd in the blockchain.
    if ($view == 'download' and isset($issuedcertificate->txhash)) {
        create_certificate_files($issuedcertificate, $metacertificate);

        // TODO check what happens when content is changing.
        $url = new moodle_url(
            '/mod/ilddigitalcert/download.php',
            array('icid' => $issuedcertificate->id, 'cmid' => $cm->id, 'download' => $download)
        );
        redirect($url);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('pluginname', 'mod_ilddigitalcert'));

    // TODO only show if already in blockchain, else:...
    if (!isset($issuedcertificate->txhash)) {
        \core\notification::info(get_string('cert_waiting_for_registration', 'mod_ilddigitalcert'));
    }
    echo html_writer::link(
        new moodle_url('/mod/ilddigitalcert/view.php?id=' . $id . '&view=html&ueid=' . $ueid),
        get_string('html', 'mod_ilddigitalcert')
    );
    echo ' | ';
    echo html_writer::link(
        new moodle_url('/mod/ilddigitalcert/view.php?id=' . $id . '&view=data&ueid=' . $ueid),
        get_string('data', 'mod_ilddigitalcert')
    );
    if (isset($issuedcertificate->txhash)) {
        // Create certificate files, that can be downloaded.
        create_certificate_files($issuedcertificate, $metacertificate);

        echo '<br />' . get_string('download') . ': ';
        $pdf = true; // TODO Add the option to disable the pdf download in the settings.
        if ($pdf) {
            echo html_writer::link(
                new moodle_url('/mod/ilddigitalcert/view.php?id=' . $id . '&view=download&download=pdf&ueid=' . $ueid),
                get_string('pdf', 'mod_ilddigitalcert')
            );
        }
        if (
            '0' != get_config('mod_ilddigitalcert', 'dchost') and
            '0' != get_config('mod_ilddigitalcert', 'dcxapikey')
        ) {
            create_certificate_files($issuedcertificate, $metacertificate);

            echo ' | ';
            echo html_writer::link(
                new moodle_url(
                    '/mod/ilddigitalcert/send_to_wallet.php?id=' . $cm->id
                ),
                get_string('send_to_wallet', 'mod_ilddigitalcert')
            );
        }
    }

    if ($view == 'data') {
        echo '<div><p>';
        display_metadata($metacertificate->get_ob(false));
        echo '</p></div>';
    } else if ($view == 'html') {
        echo '<div id="zertifikat-page" style="border: 0px solid #bfbfbf;margin: 20px 0px;max-width: 800px;">';
        echo get_certificatehtml($cm->instance, $metacertificate->get_ob());
        echo '</div>';

        if (isset($issuedcertificate->txhash)) {
            // Show QR-Code.
            echo '<br />';
            echo '<h3>' . get_string('verify_authenticity', 'mod_ilddigitalcert') . '</h3>';

            $salt = get_token($issuedcertificate->institution_token);
            $hash = $metacertificate->get_ob_hash($salt);
            $url = new moodle_url('/mod/ilddigitalcert/verify.php', array('hash' => $hash));
            $img = '<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' .
                $url . '&choe=UTF-8" title="' . get_string('verify_authenticity', 'mod_ilddigitalcert') . '" />';
            echo html_writer::link($url, $img);
        }
    }
    echo $OUTPUT->footer();
}
