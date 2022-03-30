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

namespace mod_ilddigitalcert\output\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir. "/tablelib.php");


/**
 * Table that lists ilddigitalcert certificates.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2022, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_table extends \table_sql {

    /**
     * Table defintion.
     *
     * @param string $uniqueid
     * @param boolean $showactions Wether action buttons should be added to the table, that allow managing the certificates.
     * @param int|null $courseid If this is null, the course column won't be included.
     * @param int $userid If this is null, the user name column won't be included.
     * @param string $lang The target language for the lang_strings used by the table.
     */
    public function __construct($uniqueid, $showactions = false, $courseid = null, $userid = null, $lang = null) {
        parent::__construct($uniqueid);
        $this->lang = $lang;

        // Define headers and columns.
        if ($showactions === true) {
            $headers[] = \html_writer::checkbox('check-all', null, false, null, array('id' => 'm-element-select-all-certs'));
            $columns[] = 'certid';
        }

        $headers[] = (new \lang_string('status'))->out($lang);
        $columns[] = 'status';
        $headers[] = (new \lang_string('title', 'mod_ilddigitalcert'))->out($lang);
        $columns[] = 'name';

        if ($userid === null) {
            $headers[] = (new \lang_string('recipient', 'mod_ilddigitalcert'))->out($lang);
            $columns[] = 'fullname';
        }
        if ($courseid === null) {
            $headers[] = (new \lang_string('course'))->out($lang);
            $columns[] = 'courseshortname';
        }

        $headers[] = (new \lang_string('startdate', 'mod_ilddigitalcert'))->out($lang);
        $columns[] = 'issued_on';

        if ($showactions === true) {
            $bulkoptions = array(
                '' => (new \lang_string('selectanaction'))->out($lang),
                'toblockchain' => (new \lang_string('toblockchain', 'mod_ilddigitalcert'))->out($lang),
                'reissue' => (new \lang_string('reissue', 'mod_ilddigitalcert'))->out($lang),
                'revoke' => (new \lang_string('revoke', 'mod_ilddigitalcert'))->out($lang),
            );
            $bulkactions = \html_writer::select(
                $bulkoptions,
                'bulk_actions',
                'selectanaction',
                null,
                array('id' => 'm-element-bulk-actions', 'class' => 'form-control')
            );
            $bulkactions .= \html_writer::empty_tag(
                'input',
                array(
                    'id' => 'm-element-bulk-actions__button',
                    'class' => ' btn btn-secondary',
                    'type' => 'button',
                    'value' => (new \lang_string('go'))->out($lang)
                )
            );

            $headers[] = $bulkactions;
            $columns[] = 'actions';
        }

        // Define the list of columns to show.
        $this->define_columns($columns);

        $this->column_class('certid', 'col-select');
        $this->column_class('status', 'col-status');
        $this->column_class('name', 'col-title');
        $this->column_class('fullname', 'col-recipient');
        $this->column_class('courseshortname', 'col-course');
        $this->column_class('issued_on', 'col-startdate');
        $this->column_class('actions', 'col-actions');

        // Define the titles of columns to show in header.
        $this->define_headers($headers);

        // Set preferences.
        $this->is_downloadable(false);
        $this->initialbars(false);
        $this->set_attribute('class', 'm-element-certs-table');
        $this->sortable(true, 'issued_on', SORT_DESC);
        $this->no_sorting('certid');
        $this->no_sorting('actions');
        $this->collapsible(false);
    }

    /**
     * This function is called for each data row to allow processing of the
     * status value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return html img displaying the current certificate status only
     *     when not downloading.
     */
    protected function col_certid($values) {
        // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading()) {
            return $values->certid;
        } else {
            $attributes = array('class' => 'm-element-select-cert');
            if (isset($values->status)) {
                $attributes["disabled"] = 'true';
            }
            return \html_writer::checkbox('select-cert' . $values->certid, $values->certid, false, null, $attributes);
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * status value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return html img displaying the current certificate status only
     *     when not downloading.
     */
    protected function col_status($values) {
        // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading()) {
            return $values->status;
        } else {
            if (isset($values->status)) {
                return '<img height="32px" title="'
                    . (new \lang_string('registered_and_signed', 'mod_ilddigitalcert'))->out($this->lang)
                    . '" src="' .  new \moodle_url('/mod/ilddigitalcert/pix/blockchain-block.svg')
                    . '" value="' . $values->status . '">';
            } else {
                return '<img height="32px" title="'
                    . (new \lang_string('issued', 'mod_ilddigitalcert'))->out($this->lang)
                    . '" src="' . new \moodle_url('/mod/ilddigitalcert/pix/blockchain-certificate.svg') . '">';
            };
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * certificates name value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return certificate name with link to certificate only
     *     when not downloading.
     */
    protected function col_name($values) {
        // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading()) {
            return $values->name;
        } else {
            return \html_writer::link(
                 new \moodle_url(
                     '/mod/ilddigitalcert/view.php',
                     array(
                        'id' => $values->cmid,
                        'issuedid' => $values->certid,
                        'ueid' => $values->enrolmentid ?? 0
                    )
                ),
                $values->name,
            );
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * courseshortname value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return course shortname with link to course only
     *     when not downloading.
     */
    protected function col_courseshortname($values) {
        // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading()) {
            return $values->courseshortname;
        } else {
            return \html_writer::link(
               new \moodle_url('/course/view.php', array('id' => $values->courseid)),
               $values->courseshortname
            );
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * issued_on value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return formated date of issueance.
     */
    protected function col_issued_on($values) {
        // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading()) {
            return $values->issued_on;
        } else {
            return userdate($values->issued_on, get_string('strftimedatetimeshort', 'langconfig'));
        }
    }


    /**
     * This function is called for each data row to allow processing of the
     * issued_on value.
     *
     * @param \stdClass $values Contains object with all the values of record.
     * @return $string Return formated date of issueance.
     */
    protected function col_actions($values) {
        // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading()) {
            return $values->issued_on;
        } else {
            if (isset($values->status)) {
                 // TODO check if revoked.
                // TODO if revoked: unrevoke certificate.
                $actions = '<div class="m-element-action-row">';
                $revokestring = (new \lang_string('revoke', 'mod_ilddigitalcert'))->out($this->lang);
                $actions .= '<button class="m-element-action btn btn-secondary" value="' . $values->certid
                    . '" action="revoke"><img title="' . $revokestring
                    . '" src="' . new \moodle_url('/mod/ilddigitalcert/pix/revoke_black_24dp.svg') . '"> '
                    . $revokestring . '</button>';
                $actions .= '</div>';
                return $actions;
            } else {
                // To-Blockchain Action.
                $actions = '<div class="m-element-action-row">';
                $toblockchainstring = (new \lang_string('toblockchain', 'mod_ilddigitalcert'))->out($this->lang);
                $actions .= '<button class="m-element-action btn btn-secondary" value="' . $values->certid
                    . '" action="toblockchain"><img title="' . $toblockchainstring
                    . '" src="' . new \moodle_url('/mod/ilddigitalcert/pix/sign_black_24dp.svg') . '"> '
                    . $toblockchainstring . '</button>';
                // Reissue action.
                $reissuestring = (new \lang_string('reissue', 'mod_ilddigitalcert'))->out($this->lang);
                $actions .= '<button class="m-element-action btn btn-secondary" value="' . $values->certid
                    . '" action="reissue"><img title="' . $reissuestring
                    . '" src="' . new \moodle_url('/mod/ilddigitalcert/pix/reissue_black_24dp.svg') . '">'
                    . $reissuestring . '</button>';
                $actions .= '</div>';
                return $actions;
            }
        }
    }
}
