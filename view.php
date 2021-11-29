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
    print_error(get_string('missingidandcmid', 'mod_ilddigitalcert'));
}

require_login();
$modulecontext = context_module::instance($cm->id);

// Wenn parameter $ueid aus overview.php übergeben ist kein kurslogin nötig um alte zertifikate auch zu sehen.
if ($ueid == 0) {
    require_login($course, true, $cm);

    $PAGE->set_url('/mod/ilddigitalcert/view.php', array('id' => $cm->id));
    $PAGE->set_title(format_string($moduleinstance->name));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->set_context($modulecontext);
} else {
    $context = context_system::instance();

    $PAGE->set_url('/mod/ilddigitalcert/view.php', array('id' => $cm->id, 'ueid' => $ueid));
    $PAGE->set_pagelayout('admin');
    $PAGE->set_title(format_string($moduleinstance->name));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->set_context($context);
}

if (isguestuser()) {
    redirect($CFG->wwwroot . '/login/');
}

$id = $cm->id;

// Zertifikat ansehen als Teacher/certifier.
if ($issuedid > 0 and has_capability('moodle/grade:viewall', context_course::instance($course->id))) {
    $issuedcertificate = $DB->get_record('ilddigitalcert_issued', array('id' => $issuedid));
    $certmetadatajson = $issuedcertificate->metadata;

    $metadataobj = json_decode($certmetadatajson);
    $filename = $issuedcertificate->name . '_' .
        $metadataobj->{'extensions:recipientB4E'}->givenname . '_' .
        $metadataobj->{'extensions:recipientB4E'}->surname . '_' .
        strtotime($metadataobj->issuedOn) . '.bcrt';
    $filename = 'certificate.bcrt';
    if ($view == 'download') {
        $fs = get_file_storage();
        $fileinfo = array(
            'contextid' => $modulecontext->id,     // ID of context.
            'component' => 'mod_ilddigitalcert',   // Usually = table name.
            'filearea' => 'metadata',              // Usually = table name.
            'itemid' => $issuedcertificate->id,   // Usually = ID of row in table.
            'filepath' => '/',                     // Any path beginning and ending in /.
            'filename' => $filename
        );              // Any filename.
        $file = $fs->get_file(
            $fileinfo['contextid'],
            $fileinfo['component'],
            $fileinfo['filearea'],
            $fileinfo['itemid'],
            $fileinfo['filepath'],
            $fileinfo['filename']
        );
        if ($file) {
            $file->delete();
        }

        // Institution token / salt hinzufügen damit der Hash auch richtig berechnet werden kann.
        $token = get_token($issuedcertificate->institution_token);
        $metadata = json_decode($certmetadatajson);
        $metadata->{'extensions:institutionTokenILD'} = get_extension_institutiontoken_ild($token);
        $certmetadatajson = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $fs->create_file_from_string($fileinfo, $certmetadatajson);

        $url = $CFG->wwwroot . '/mod/ilddigitalcert/download.php?id=' . $modulecontext->id .
            '&icid=' . $issuedcertificate->id . '&cmid=' . $cm->id . '&download=' . $download;
        redirect($url);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('pluginname', 'mod_ilddigitalcert'));

    echo html_writer::link(
        new moodle_url('/mod/ilddigitalcert/view.php?id=' . $id . '&issuedid=' . $issuedid . '&view=html&ueid=' . $ueid),
        get_string('html', 'mod_ilddigitalcert')
    );
    echo ' | ';
    echo html_writer::link(
        new moodle_url('/mod/ilddigitalcert/view.php?id=' . $id . '&issuedid=' . $issuedid . '&view=data&ueid=' . $ueid),
        get_string('data', 'mod_ilddigitalcert')
    );
    if (isset($issuedcertificate->txhash)) {
        echo '<br />' . get_string('download') . ': ';
        echo html_writer::link(
            new moodle_url('/mod/ilddigitalcert/view.php?id=' . $id . '&issuedid=' . $issuedid . '&view=download&ueid=' . $ueid),
            get_string('json', 'mod_ilddigitalcert')
        );
        $pdf = true; // TODO in die Settings!
        if ($pdf) {
            echo ' | ';
            echo html_writer::link(
                new moodle_url(
                    '/mod/ilddigitalcert/view.php?id=' . $id . '&issuedid=' . $issuedid . '&view=download&download=pdf&ueid=' . $ueid
                ),
                get_string('pdf', 'mod_ilddigitalcert')
            );
        }
        if (
            '0' != get_config('mod_ilddigitalcert', 'dchost') and
            '0' != get_config('mod_ilddigitalcert', 'dcxapikey') and
            '0' != get_config('mod_ilddigitalcert', 'dcconnectorid')
        ) {
            echo ' | ';
            echo html_writer::link(
                new moodle_url(
                    '/mod/ilddigitalcert/send_to_wallet.php?id=' . $cm->id
                ),
                get_string('send_to_wallet', 'mod_ilddigitalcert')
            );
        }
    }
    // TODO Zertifikat anzeigen!
    if ($view == 'data') {
        $metadata = json_decode($certmetadatajson);
        echo '<div><p>';
        display_metadata($metadata);
        echo '</p></div>';
    } else if ($view == 'html') {
        echo '<div id="zertifikat-page" style="border: 0px solid #bfbfbf;margin: 20px 0px;max-width: 800px;">';
        echo get_certificatehtml($cm->instance, $certmetadatajson);
        echo '</div>';

        if (isset($issuedcertificate->txhash)) {
            // QR-Code anzeigen.
            echo '<br />';
            echo '<h3>Zertifikat in der Blockchain überprüfen</h3>'; // TODO sprachpaket!

            $salt = get_token($issuedcertificate->institution_token);
            $metadata = json_decode($certmetadatajson);
            $metadata->{'extensions:institutionTokenILD'} = get_extension_institutiontoken_ild($salt);
            $certmetadatajson = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $hash = calculate_hash($certmetadatajson);
            $url = $CFG->wwwroot . '/mod/ilddigitalcert/verify.php?hash=' . $hash;
            $img = '<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . $url . '&choe=UTF-8"
                title="Zertifikat überprüfen" />';
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
    echo get_certificatehtml($cm->instance, json_encode(generate_certmetadata($cm, $USER)));
    echo '</div>';
    echo '<p>' . html_writer::link($CFG->wwwroot . '/mod/ilddigitalcert/view.php?id=' . $id . '&ueid=' . $ueid, get_string('back')) . '</p>';
    echo $OUTPUT->footer();
} else if (has_capability('moodle/grade:viewall', context_course::instance($course->id)) and $view != 'download') {
    // TODO elseif (has_capability('moodle/grade:viewall', context_course::instance($course->id)) and $view == 'issue_teacher')//!
    // Zertifikatsübersicht als Teacher/certifier.
    redirect($CFG->wwwroot . '/mod/ilddigitalcert/teacher_view.php?id=' . $cm->id . '&ueid=' . $ueid);
} else {
    // TODO unterscheiden ob $ueid (dann neue Funtktion get_issued_certificate) oder aktuelles enrolment!

    // View Certificate as student.
    $certmetadata = generate_certmetadata($cm, $USER);

    if ($ueid == 0) {
        $certmetadatajson = issue_certificate($certmetadata, $USER->id, $cm->id);
    } else {
        $certmetadatajson = get_issued_certificate($USER->id, $cm->id, $ueid);
    }
    if (!$certmetadatajson) {
        print_error(
            'found_no_issued_certificate',
            'mod_ilddigitalcert',
            new moodle_url(
                '/mod/ilddigitalcert/course/view.php',
                array('id' => $courseid, 'ueid' => $ueid)
            )
        );
    }
    $issuedcertificate = $DB->get_record('ilddigitalcert_issued', array('userid' => $USER->id, 'cmid' => $cm->id));

    // TODO: nur wenn in BC gespeichert.

    $bc = true;

    if ($view == 'download' and $bc) {
        // TODO: save json file if not already done.

        $fs = get_file_storage();

        $metadataobj = json_decode($certmetadatajson);
        $filename = $issuedcertificate->name . '_' .
            $metadataobj->{'extensions:recipientB4E'}->givenname . '_' .
            $metadataobj->{'extensions:recipientB4E'}->surname . '_' .
            strtotime($metadataobj->issuedOn) . '.bcrt';
        $filename = 'certificate.bcrt';
        // Prepare file record object.
        $fileinfo = array(
            'contextid' => $modulecontext->id,     // ID of context.
            'component' => 'mod_ilddigitalcert',   // usually = table name.
            'filearea' => 'metadata',              // usually = table name.
            'itemid' => $issuedcertificate->id,    // usually = ID of row in table.
            'filepath' => '/',                     // any path beginning and ending in /.
            'filename' => $filename
        );              // any filename.

        // Get file.
        $file = $fs->get_file(
            $fileinfo['contextid'],
            $fileinfo['component'],
            $fileinfo['filearea'],
            $fileinfo['itemid'],
            $fileinfo['filepath'],
            $fileinfo['filename']
        );

        // Delete it if it exists.
        if ($file) {
            $file->delete();
        }

        // Institution token / add salt to calculate hash correctly.
        $token = get_token($issuedcertificate->institution_token);
        $metadata = json_decode($certmetadatajson);
        $metadata->{'extensions:institutionTokenILD'} = get_extension_institutiontoken_ild($token);
        $certmetadatajson = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Create file.
        $fs->create_file_from_string($fileinfo, $certmetadatajson);

        // TODO check what happens when content is changing.
        $url = $CFG->wwwroot . '/mod/ilddigitalcert/download.php?id=' .
            $modulecontext->id . '&icid=' . $issuedcertificate->id . '&cmid=' . $cm->id . '&download=' . $download;
        redirect($url);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('pluginname', 'mod_ilddigitalcert'));

    // TODO only show if already in clockchain, else:...
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
        echo '<br />' . get_string('download') . ': ';
        // Backup: echo html_writer::link(new moodle_url('/mod/ilddigitalcert/view.php?id='.$id.'&view=download&ueid='.$ueid), //.
        // Backup:   get_string('json', 'mod_ilddigitalcert'));//.
        $pdf = true; // TODO in settings.
        if ($pdf) {
            // Backup: echo ' | '; //.
            echo html_writer::link(
                new moodle_url('/mod/ilddigitalcert/view.php?id=' . $id . '&view=download&download=pdf&ueid=' . $ueid),
                get_string('pdf', 'mod_ilddigitalcert')
            );
        }
        if (
            '0' != get_config('mod_ilddigitalcert', 'dchost') and
            '0' != get_config('mod_ilddigitalcert', 'dcxapikey') and
            '0' != get_config('mod_ilddigitalcert', 'dcconnectorid')
        ) {
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
        $metadata = json_decode($certmetadatajson);
        echo '<div><p>';
        display_metadata($metadata);
        echo '</p></div>';
    } else if ($view == 'html') {
        echo '<div id="zertifikat-page" style="border: 0px solid #bfbfbf;margin: 20px 0px;max-width: 800px;">';
        echo get_certificatehtml($cm->instance, $certmetadatajson);
        echo '</div>';

        if (isset($issuedcertificate->txhash)) {
            // Show QR-Code.
            echo '<br />';
            echo '<h3>Zertifikat in der Blockchain überprüfen</h3>';

            $salt = get_token($issuedcertificate->institution_token);
            $metadata = json_decode($certmetadatajson);
            $metadata->{'extensions:institutionTokenILD'} = get_extension_institutiontoken_ild($salt);
            $certmetadatajson = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $hash = calculate_hash($certmetadatajson);
            $url = $CFG->wwwroot . '/mod/ilddigitalcert/verify.php?hash=' . $hash;
            $img = '<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' .
                $url . '&choe=UTF-8" title="Zertifikat überprüfen" />';
            echo html_writer::link($url, $img);
        }
    }
    echo $OUTPUT->footer();
}
