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
 * Prints an overview of all certificates a student has reached.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->libdir.'/tablelib.php');

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/ilddigitalcert/overview.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title(format_string(get_string('pluginname', 'mod_ilddigitalcert')));
$PAGE->set_heading(get_string('pluginname', 'mod_ilddigitalcert'));

require_login();

if (isguestuser()) {
    redirect($CFG->wwwroot.'/login/');
}
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/mod/ilddigitalcert/js/pk_form.js'));

$id = optional_param('id', 0, PARAM_INT);
$ueid = optional_param('ueid', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_ALPHA);
$checkonlybc = optional_param('check_only_bc', '', PARAM_RAW);
$checkonlynonbc = optional_param('check_only_nonbc', '', PARAM_RAW);
$and = '';
if ($checkonlybc == 'check_only_bc') {
    $and = ' AND idci.certhash is not null ';
    if ($search == '') {
        $search = '%';
    }
} else if ($checkonlynonbc == 'check_only_nonbc') {
    $and = ' AND idci.certhash is null ';
    if ($search == '') {
        $search = '%';
    }
}
if ($search != '') {
    $sql = 'SELECT idci.*
                FROM {ilddigitalcert_issued} idci, {course} c
                WHERE idci.userid = :userid
                AND c.id = idci.courseid
                AND ('.$DB->sql_like('c.fullname', ':search1', false, false).'
                OR '.$DB->sql_like('idci.name', ':search2', false, false).')
                '.$and;
    $params = array('userid' => $USER->id,
                    'search1' => '%'.$search.'%',
                    'search2' => '%'.$search.'%');
    $issuedcertificates = $DB->get_records_sql($sql, $params);
    $search = '';
} else {
    $issuedcertificates = $DB->get_records('ilddigitalcert_issued', array('userid' => $USER->id));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('overview', 'mod_ilddigitalcert'));

$intro = get_string('overview_intro', 'mod_ilddigitalcert');

echo html_writer::tag('p', $intro, null);

$table = new flexible_table('MODULE_TABLE');
$table->define_columns(array('icon',
                                'name',
                                'issuedon',
                                'course'));
$table->define_headers(array(get_string('status'),
                                get_string('title', 'mod_ilddigitalcert'),
                                get_string('startdate', 'mod_ilddigitalcert'),
                                get_string('course')));
$table->define_baseurl($CFG->wwwroot.'/mod/ilddigitalcert/overview.php');
$table->set_attribute('class', 'admintable generaltable');
$table->sortable(false, 'name', SORT_ASC);
$table->setup();

foreach ($issuedcertificates as $issuedcertificate) {
    $data = array();
    $icon = '<img height="32px" title="'.
               get_string('pluginname', 'mod_ilddigitalcert').
               '" src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/blockchain-certificate.svg">';
    if (isset($issuedcertificate->txhash)) {
        $icon = '<img height="32px" title="'.
                   get_string('registered_and_signed', 'mod_ilddigitalcert').
                   '" src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/blockchain-block.svg">';
    }
    $data[] = $icon;

    // Zertifikat anzeigen.
    $data[] = html_writer::link(
                new moodle_url('/mod/ilddigitalcert/view.php?id='.
                  $issuedcertificate->cmid.'&ueid='.$issuedcertificate->enrolmentid),
                $issuedcertificate->name);
    $data[] = date('d.m.Y - H:i', $issuedcertificate->timecreated);
    $course = '';
    if ($course = $DB->get_record('course', array('id' => $issuedcertificate->courseid))) {
        $coursename = $course->fullname;
    }
    $data[] = html_writer::link(new moodle_url('/course/view.php?id='.$issuedcertificate->courseid), $coursename);

    $table->add_data($data);
}
// Suchfeld.
echo '<p>';
echo '<form action="'.new moodle_url('/mod/ilddigitalcert/overview.php?id='.$id.'&ueid='.$ueid).'" class="searchform">';
echo '<div>';
echo '<input type="hidden" name="id" value="'.$id.'" />';
echo '<input type="checkbox" id="check_only_bc" name="check_only_bc" value="check_only_bc">'.
     get_string('only_blockchain', 'mod_ilddigitalcert').'<br />';
echo '<input type="checkbox" id="check_only_nonbc" name="check_only_nonbc" value="check_only_nonbc">'.
     get_string('only_nonblockchain', 'mod_ilddigitalcert').'<br />';
echo '<input type="text" id="search" name="search" value="'.s($search).'" />&nbsp;';
echo '<input type="submit" value="'.get_string('search').'" style="margin-top: 9px;height: 27px;padding-top: 2px;"/>';
echo '&nbsp;'.html_writer::link(new moodle_url('/mod/ilddigitalcert/overview.php?id='.$id.'&ueid='.$ueid), get_string('cancel'));
echo '</div>';
echo '</form>';
echo '</p>';

$table->print_html();

echo $OUTPUT->footer();