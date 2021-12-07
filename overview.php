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


require_once(__DIR__ . '/../../config.php');
require_once('locallib.php');
require_once(__DIR__ . '/search_certificates_form.php');
require_once(__DIR__ . '/to_blockchain_form.php');
require_once(__DIR__ . '/reissue_form.php');


$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/ilddigitalcert/overview.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title(format_string(get_string('pluginname', 'mod_ilddigitalcert')));
$PAGE->set_heading(get_string('pluginname', 'mod_ilddigitalcert'));

require_login();

if (isguestuser()) {
    redirect($CFG->wwwroot . '/login/');
}

if (get_user_preferences('mod_ilddigitalcert_certifier', false, $USER)) {
    redirect($CFG->wwwroot . '/mod/ilddigitalcert/certifier_overview.php');
}

// $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/ilddigitalcert/js/pk_form.js'));


// Instantiate search form.
$search_form = new mod_ilddigialcert_search_certificates_form();

// Get search results.
$issuedcertificates = array();
if ($search_form_data = $search_form->get_data()) {
    if ($search_form_data->search_query || $search_form_data->search_filter) {
        $sql = 'SELECT idci.*, c.fullname, c.shortname
            FROM {ilddigitalcert_issued} idci, {course} c
            WHERE idci.courseid = c.id
            AND idci.userid = :userid';

        $params = array('userid' => $USER->id);

        if ($search_form_data->search_query !== '') {
            $sql .= ' AND (' . $DB->sql_like('c.fullname', ':search1', false, false) . '
                OR ' . $DB->sql_like('c.shortname', ':search2', false, false) . '
                OR ' . $DB->sql_like('idci.name', ':search3', false, false) . ')';
            $params['search1'] = '%' . $search_form_data->search_query . '%';
            $params['search2'] = '%' . $search_form_data->search_query . '%';
            $params['search3'] = '%' . $search_form_data->search_query . '%';
        }

        if ($search_form_data->search_filter === 'only_bc') {
            $sql .= ' AND idci.certhash is not null ';
        } else if ($search_form_data->search_filter === 'only_nonbc') {
            $sql .= ' AND idci.certhash is null ';
        }

        $issuedcertificates = $DB->get_records_sql($sql, $params);
    } else {
        $issuedcertificates = $DB->get_records('ilddigitalcert_issued', array('userid' => $USER->id));
    }
} else {
    $issuedcertificates = $DB->get_records('ilddigitalcert_issued', array('userid' => $USER->id));

    $search_form_data = (object) [
        'search_query' => '',
        'search_filter' => ''
    ];
}

// Set default data (if any).
$search_form->set_data($search_form_data);



// Build page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('overview', 'mod_ilddigitalcert'));

$template_data = array(
    'search_form' => $search_form->render(),
    'search_count' => count($issuedcertificates),
    'certs_table' => \mod_ilddigitalcert\manager::render_certs_table($issuedcertificates, false, null, $USER->id),
);

echo $OUTPUT->render_from_template('mod_ilddigitalcert/overview', $template_data);

echo $OUTPUT->footer();
