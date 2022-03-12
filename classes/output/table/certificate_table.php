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
 * Table that lists ilddigitalcert certificates.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2022, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ilddigitalcert\output\table;

use moodle_url;
use pix_icon;

defined('MOODLE_INTERNAL') || die();

require "$CFG->libdir/tablelib.php";

class certificate_table extends \table_sql {

    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    function __construct($uniqueid, $show_actions = false, $courseid = null, $userid = null, $lang = null) {
        parent::__construct($uniqueid);
        $this->lang = $lang;

        // Define headers and columns.
        if ($show_actions === true) {
            $headers[] = \html_writer::checkbox('check-all', null, false, null, array('id' => 'm-element-select-all-certs'));
            // $headers[] = 'certid';
            $columns[] = 'certid';
            // $align[] = 'left';
        }

        $headers[] = (new \lang_string('status'))->out($lang);
        $columns[] = 'status';
        // $align[] = 'center';
        $headers[] = (new \lang_string('title', 'mod_ilddigitalcert'))->out($lang);
        $columns[] = 'name';
        // $align[] = 'left';

        if (!\is_scalar($userid)) {
            $headers[] = (new \lang_string('recipient', 'mod_ilddigitalcert'))->out($lang);
            $columns[] = 'fullname';
            // $align[] = 'left';
        }
        if (!\is_scalar($courseid)) {
            $headers[] = (new \lang_string('course'))->out($lang);
            $columns[] = 'courseshortname';
            // $align[] = 'left';
        }

        $headers[] = (new \lang_string('startdate', 'mod_ilddigitalcert'))->out($lang);
        $columns[] = 'issued_on';
        // $align[] = 'left';

        if ($show_actions === true) {
            $bulk_options = array(
                '' => (new \lang_string('selectanaction'))->out($lang),
                'toblockchain' => (new \lang_string('toblockchain', 'mod_ilddigitalcert'))->out($lang),
                'reissue' => (new \lang_string('reissue', 'mod_ilddigitalcert'))->out($lang),
                'revoke' => (new \lang_string('revoke', 'mod_ilddigitalcert'))->out($lang),
            );
            $bulk_actions = \html_writer::select(
                $bulk_options,
                'bulk_actions', 
                'selectanaction', 
                null, 
                array('id' => 'm-element-bulk-actions', 'class' => 'form-control')
            );
            $bulk_actions .= \html_writer::empty_tag(
                'input', 
                array(
                    'id' => 'm-element-bulk-actions__button', 
                    'class' => ' btn btn-secondary', 
                    'type' => 'button', 
                    'value' => (new \lang_string('go'))->out($lang)
                )
            );

            $headers[] = $bulk_actions;
            $columns[] = 'actions';
            // $align[] = 'left';
        }

        // $table->align = $align;


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
    * @param object $values Contains object with all the values of record.
    * @return $string Return html img displaying the current certificate status only
    *     when not downloading.
    */
    function col_certid($values) {
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
    * @param object $values Contains object with all the values of record.
    * @return $string Return html img displaying the current certificate status only
    *     when not downloading.
    */
    function col_status($values) {
        // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading()) {
            return $values->status;
        } else {
            if (isset($values->status)) {
                return '<img height="32px" title="' 
                    . (new \lang_string('registered_and_signed', 'mod_ilddigitalcert'))->out($this->lang) 
                    . '" src="' .  new moodle_url('/mod/ilddigitalcert/pix/blockchain-block.svg') 
                    . '" value="' . $values->status . '">';
            } else {
                return '<img height="32px" title="' 
                    . (new \lang_string('issued', 'mod_ilddigitalcert'))->out($this->lang)
                    . '" src="' . new moodle_url('/mod/ilddigitalcert/pix/blockchain-certificate.svg') . '">';
            };
        }
    }

    /**
     * This function is called for each data row to allow processing of the
     * certificates name value.
     *
     * @param object $values Contains object with all the values of record.
     * @return $string Return certificate name with link to certificate only
     *     when not downloading.
     */
    function col_name($values) {
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
    * @param object $values Contains object with all the values of record.
    * @return $string Return course shortname with link to course only
    *     when not downloading.
    */
   function col_courseshortname($values) {
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
   * @param object $values Contains object with all the values of record.
   * @return $string Return formated date of issueance.
   */
    function col_issued_on($values) {
        // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading()) {
            return $values->issued_on;
        } else {
            //   return date('d.m.Y - H:i', $values->issued_on);
            return userdate($values->issued_on, get_string('strftimedatetimeshort', 'langconfig'));
        }
    }

    
   /**
   * This function is called for each data row to allow processing of the
   * issued_on value.
   *
   * @param object $values Contains object with all the values of record.
   * @return $string Return formated date of issueance.
   */
  function col_actions($values) {
    // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading()) {
            return $values->issued_on;
        } else {
            if (isset($values->status)) {
                 // TODO check if revoked.
                // TODO if revoked: unrevoke certificate.
                $actions = '<div class="m-element-action-row">';
                $revoke_string = (new \lang_string('revoke', 'mod_ilddigitalcert'))->out($this->lang);
                $actions .= '<button class="m-element-action btn btn-secondary" value="' . $values->certid 
                    . '" action="revoke"><img title="' . $revoke_string
                    . '" src="' . new moodle_url('/mod/ilddigitalcert/pix/revoke_black_24dp.svg') . '"> '
                    . $revoke_string . '</button>';
                $actions .= '</div>';
                return $actions;
            } else {
                // To-Blockchain Action
                $actions = '<div class="m-element-action-row">';
                $toblockchain_string = (new \lang_string('toblockchain', 'mod_ilddigitalcert'))->out($this->lang);
                $actions .= '<button class="m-element-action btn btn-secondary" value="' . $values->certid 
                    . '" action="toblockchain"><img title="' . $toblockchain_string
                    . '" src="' . new moodle_url('/mod/ilddigitalcert/pix/sign_black_24dp.svg') . '"> '
                    . $toblockchain_string . '</button>';
                // Reissue action
                $reissue_string = (new \lang_string('reissue', 'mod_ilddigitalcert'))->out($this->lang);
                $actions .= '<button class="m-element-action btn btn-secondary" value="' . $values->certid 
                    . '" action="reissue"><img title="' . $reissue_string
                    . '" src="' . new moodle_url('/mod/ilddigitalcert/pix/reissue_black_24dp.svg') . '">'
                    . $reissue_string . '</button>';
                $actions .= '</div>';
                return $actions;
            }
        }
    }
}