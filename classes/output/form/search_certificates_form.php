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
 * Form that lets users search for specific cerificats.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2022, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ilddigitalcert\output\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/formslib.php");

/**
 * Form that lets users search for specific cerificats.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2021, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_certificates_form extends \moodleform {
    /**
     * Form definition.
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('html', '<div class="m-element-search-form col-md-9 form-inline align-items-start felement">');

        // Search query input.
        $search_query_attributes = array(
            'id' => 'm-element-search__query',
            'placeholder' => get_string('search'),
        );
        $mform->addElement('text', 'search_query', null, $search_query_attributes);
        $mform->setType('search_query', PARAM_NOTAGS);

        // Search filter dropdown select.
        $filter_attributes = array(
            'id' => 'm-element-search__filter',
        );
        $filter_options = array(
            '' => get_string('all'),
            'only_bc' => get_string('only_blockchain', 'mod_ilddigitalcert'),
            'only_nonbc' => get_string('only_nonblockchain', 'mod_ilddigitalcert'),
        );
        $mform->addElement('select', 'search_filter', '', $filter_options, $filter_attributes);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('submit', 'search_submit', get_string('search'));
        $mform->addElement('submit', 'search_reset', get_string('reset'));

        $mform->addElement('html', '</div>');
    }

    /**
     * Gets input data of submitted form.
     *
     * @return object
     **/
    public function get_data() {
        $data = parent::get_data();

        if (empty($data)) {
            return false;
        }

        return $data;
    }


    /**
     * Builds a sql WHERE statement to search for certificates that meet the conditions defined in the form data.
     *
     * @return array Array containing an $sql WHERE statement and a set of $params.
     **/
    public function action() {
        global $DB;
        
        // Get form data.
        if(!$data = $this->get_data()) return null;

        $search_query = $data->search_query;
        $search_filter = $data->search_filter;
        if (!$search_query && !$search_filter) return null;

        $sql = '';
        $params = array();
        
        if($data->courseid) {
            $sql .= ' AND c.id = :courseid';
            $params['courseid'] = $data->courseid;    
        }
        
        if($data->userid) {
            $sql .= ' AND  u.id = :userid';
            $params['userid'] = $data->userid;    
        }

        if ($search_query !== '') {
            $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
            $sql .= ' AND (' . $DB->sql_like($fullname, ':search1', false, false) . '
            OR ' . $DB->sql_like('c.shortname', ':search2', false, false) . '
            OR ' . $DB->sql_like('c.fullname', ':search3', false, false) . '
                OR ' . $DB->sql_like('idci.name', ':search4', false, false) . ')';
            $params['search1'] = '%' . $search_query . '%';
            $params['search2'] = '%' . $search_query . '%';
            $params['search3'] = '%' . $search_query . '%';
            $params['search4'] = '%' . $search_query . '%';
        }

        if ($search_filter === 'only_bc') {
            $sql .= ' AND idci.txhash is not null ';
        } else if ($search_filter === 'only_nonbc') {
            $sql .= ' AND idci.txhash is null ';
        }

        return array($sql, $params);
    }
}
