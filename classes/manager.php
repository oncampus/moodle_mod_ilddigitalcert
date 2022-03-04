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
 * Library of utility functions for mod_ilddigitalcert.
 *
 * @package    mod_ilddigitalcert
 * @copyright  2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ilddigitalcert;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/ilddigitalcert/locallib.php');

/**
 * Library of utility functions for mod_ilddigitalcert.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2021, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /**
     * Renders the given certificates array as a html table.
     *
     * @param array $certificates Certificates to be listed in the table.
     * @param int $course
     * @return string HTML representation of a certificates table.
     */
    static function render_certs_table($certificates, $show_actions = false, $courseid = null, $userid = null, $lang = null) {
        global $CFG, $DB;

        if (!$certificates || empty($certificates)) {
            return '';
        }

        // Create certificates table.
        $table = new \html_table();
        $table->attributes['class'] = 'generaltable m-element-certs-table';


        if ($show_actions === true) {
            $head[] = \html_writer::checkbox('check-all', null, false, null, array('id' => 'm-element-select-all-certs'));
            $align[] = 'left';
        }

        $head[] = (new \lang_string('status'))->out($lang);
        $align[] = 'center';
        $head[] = (new \lang_string('title', 'mod_ilddigitalcert'))->out($lang);
        $align[] = 'left';

        if (!\is_scalar($userid)) {
            $head[] = (new \lang_string('recipient', 'mod_ilddigitalcert'))->out($lang);
            $align[] = 'left';
        }
        if (!\is_scalar($courseid)) {
            $head[] = (new \lang_string('course'))->out($lang);
            $align[] = 'left';
        }

        $head[] = (new \lang_string('startdate', 'mod_ilddigitalcert'))->out($lang);
        $align[] = 'left';

        if ($show_actions === true) {
            $bulk_options = array(
                '' => (new \lang_string('selectanaction'))->out($lang),
                'toblockchain' => (new \lang_string('toblockchain', 'mod_ilddigitalcert'))->out($lang),
                'reissue' => (new \lang_string('reissue', 'mod_ilddigitalcert'))->out($lang),
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

            $head[] = $bulk_actions;
            $align[] = 'left';
        }

        $table->head  = $head;
        $table->align = $align;

        // Fill table.
        foreach ($certificates as $certificate) {
            $row = array();
            if ($show_actions === true) {
                $attributes = array('class' => 'm-element-select-cert');
                if (isset($certificate->txhash)) {
                    $attributes["disabled"] = 'true';
                }
                $row[] = \html_writer::checkbox('select-cert' . $certificate->id, $certificate->id, false, null, $attributes);
            }
            $icon = '<img height="32px" title="' . (new \lang_string('pluginname', 'mod_ilddigitalcert'))->out($lang) . '"
        src="' . $CFG->wwwroot . '/mod/ilddigitalcert/pix/blockchain-certificate.svg">';
            if (isset($certificate->txhash)) {
                $icon = '<img height="32px" title="' . (new \lang_string('registered_and_signed', 'mod_ilddigitalcert'))->out($lang) . '"
            src="' . $CFG->wwwroot . '/mod/ilddigitalcert/pix/blockchain-block.svg">';
            }
            $row[] = $icon;


            // TODO Zertifikat anzeigen.
            $row[] = \html_writer::link(
                $CFG->wwwroot . '/mod/ilddigitalcert/view.php?id=' .
                    $certificate->cmid . '&issuedid=' . $certificate->id . '&ueid=' . ($certificate->enrolmentid ?? 0),
                $certificate->name
            );


            if (!\is_scalar($userid)) {
                $user = $DB->get_record_sql(
                    'select id, firstname, lastname from {user} where id = :id ',
                    array('id' => $certificate->userid)
                );
                $row[] = \html_writer::link(
                    new \moodle_url('/user/view.php?id=' .
                        $user->id  . ($courseid ? ('&course=' . $courseid) : '') . '&ueid=' . ($certificate->enrolmentid ?? 0)),
                    $user->firstname . ' ' . $user->lastname
                );
            }

            if (!\is_scalar($courseid)) {
                $course = $DB->get_record('course', array('id' => $certificate->courseid), 'id, shortname', IGNORE_MISSING);
                $row[] = \html_writer::link(
                    new \moodle_url('/course/view.php?id=' . $course->id),
                    $course->shortname
                );
            }

            $row[] = date('d.m.Y - H:i', $certificate->timecreated);

            if ($show_actions === true) {
                // TODO Zertifikat neu ausstellen.
                // Zertifikat in Blockchain speichern.
                if (!isset($certificate->txhash)) {
                    // To-Blockchain Action
                    $actions = '<div class="m-element-action-row">';
                    $actions .= '<button class="m-element-sign-cert btn btn-secondary" value="' . $certificate->id . '">
                <img title="' . (new \lang_string('reissue', 'mod_ilddigitalcert'))->out($lang) .
                        '" src="' . $CFG->wwwroot . '/mod/ilddigitalcert/pix/sign_black_24dp.svg"> ' .
                        (new \lang_string('toblockchain', 'mod_ilddigitalcert'))->out($lang) . '</button>';
                    // Reissue action
                    // $actions .= html_writer::link(
                    //     new moodle_url('/mod/ilddigitalcert/teacher_view.php?id=' .
                    //         $certificate->cmid . '&reissueid=' . $certificate->id . '&action=reissue'),
                    //     '<img alt="' . (new \lang_string('reissue', 'mod_ilddigitalcert'))->out($lang) . '" title="' . (new \lang_string('reissue', 'mod_ilddigitalcert') . '"
                    //     src="' . $CFG->wwwroot . '/mod/ilddigitalcert/pix/refresh_grey_24x24.png">'
                    // );
                    $actions .= '<button class="m-element-reissue btn btn-secondary" value="' . $certificate->id . '">
                <img title="' . (new \lang_string('reissue', 'mod_ilddigitalcert'))->out($lang) .
                        '" src="' . $CFG->wwwroot . '/mod/ilddigitalcert/pix/reissue_black_24dp.svg"> Reissue
            </button>';
                    $actions .= '</div>';
                    $row[] = $actions;
                } else {
                    // TODO check revoked.
                    // TODO if not revoked: revoke certificate.
                    $row[] = '';
                    // TODO if revoked: unrevoke certificate.
                }
            }

            $table->data[] = $row;
        }

        return \html_writer::table($table);
    }


    /**
     * Returns an object containing all relevant data to send a moodle notification.
     *
     * @param string $name
     * @param \core_user $to_user Recipient.
     * @param string $subject
     * @param string $html
     * @param string $text
     * @return object Message object.
     */
    public static function get_message($name, $to_user, $subject, $html, $text, $contexturl = null, $contexturlname = null) {

        $message = new \core\message\message();
        $message->component = 'mod_ilddigitalcert'; // Your plugin's name
        $message->name = $name; // Your notification name from message.php
        $message->userfrom = \core_user::get_noreply_user(); // If the message is 'from' a specific user you can set them here
        $message->userto = $to_user;
        $message->subject = $subject;
        $message->fullmessageformat = FORMAT_HTML;

        $message->fullmessagehtml = $html;

        $message->fullmessage = $text;

        $message->notification = 1; // Because this is a notification generated from Moodle, not a user-to-user message

        if ($contexturl) {
            $message->contexturl = $contexturl; // A relevant URL for the notification
        }
        if ($contexturlname) {
            $message->contexturlname = $contexturlname;
        }

        return $message;
    }
}
