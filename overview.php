<?php
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
#/*
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/mod/ilddigitalcert/js/pk_form.js'));

$id = optional_param('id', 0, PARAM_INT);
$ueid = optional_param('ueid', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_ALPHA);
$check_only_bc = optional_param('check_only_bc', '', PARAM_RAW);
$check_only_nonbc = optional_param('check_only_nonbc', '', PARAM_RAW);
$and = '';
if ($check_only_bc == 'check_only_bc') {
	$and = ' AND idci.certhash is not null ';
	if ($search == '') {
		$search = '%';
	}
}
elseif ($check_only_nonbc == 'check_only_nonbc') {
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
    $issued_certificates = $DB->get_records_sql($sql, $params);
	$search = '';
}
else {
    $issued_certificates = $DB->get_records('ilddigitalcert_issued', array('userid' => $USER->id));
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

foreach ($issued_certificates as $issued_certificate) {
    $data = array();
    $icon = '<img height="32px" title="'.get_string('pluginname', 'mod_ilddigitalcert').'" src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/blockchain-certificate.svg">';
    if (isset($issued_certificate->txhash)) {
        $icon .= '<img height="32px" title="'.get_string('registered_and_signed', 'mod_ilddigitalcert').'" src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/blockchain-block.svg">';
    }
    $data[] = $icon;
    //$user = $DB->get_record_sql('select id, firstname, lastname from {user} where id = :id ', array('id' => $issued_certificate->userid));
    
    // Zertifikat anzeigen 
    $data[] = html_writer::link(new moodle_url('/mod/ilddigitalcert/view.php?id='.$issued_certificate->cmid.'&ueid='.$issued_certificate->enrolmentid), $issued_certificate->name);
    $data[] = date('d.m.Y - H:i', $issued_certificate->timecreated);
    $course = '';
    if ($course = $DB->get_record('course', array('id' => $issued_certificate->courseid))) {
        $coursename = $course->fullname;
    }
    $data[] = html_writer::link(new moodle_url('/course/view.php?id='.$issued_certificate->courseid), $coursename);
    
    $table->add_data($data);
}
#*/
// Suchfeld
echo '<p>';
echo '<form action="'.new moodle_url('/mod/ilddigitalcert/overview.php?id='.$id.'&ueid='.$ueid).'" class="searchform">';
echo '<div>';
echo '<input type="hidden" name="id" value="'.$id.'" />';
//echo '<input type="hidden" name="ueid" value="'.$ueid.'" />';
echo '<input type="checkbox" id="check_only_bc" name="check_only_bc" value="check_only_bc">'.get_string('only_blockchain', 'mod_ilddigitalcert').'<br />';
echo '<input type="checkbox" id="check_only_nonbc" name="check_only_nonbc" value="check_only_nonbc">'.get_string('only_nonblockchain', 'mod_ilddigitalcert').'<br />';
echo '<input type="text" id="search" name="search" value="'.s($search).'" />&nbsp;';
echo '<input type="submit" value="'.get_string('search').'" style="margin-top: 9px;height: 27px;padding-top: 2px;"/>';
echo '&nbsp;'.html_writer::link(new moodle_url('/mod/ilddigitalcert/overview.php?id='.$id.'&ueid='.$ueid), get_string('cancel'));
echo '</div>';
echo '</form>';
echo '</p>';

$table->print_html();

echo $OUTPUT->footer();
