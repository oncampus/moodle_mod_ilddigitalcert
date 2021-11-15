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
     * Returns a html table listing all the given certificates.
     *
     * @param array $certs Certifiactes.
     * @param bool $show_actions Enables column with action buttons/links.
     * @return string Html table.
     */
    public static function get_certs_table($certs, $show_actions = true) {
        global $DB, $CFG;

        // Setup table structure.
        $table = new \html_table();
        $table->head = array(get_string('status'),
            get_string('title', 'mod_ilddigitalcert'),
            get_string('recipient', 'mod_ilddigitalcert'),
            get_string('startdate', 'mod_ilddigitalcert'),
        );
        $align = array('left ', 'left', 'left', 'left');
        if($show_actions) {
            $table->head[] = get_string('actions');
            $align[] = 'left';
        }
        $table->attributes['class'] = 'generaltable';


        // Format and add data to table.
        foreach ($certs as $cert) {
            if(!isset($cert->enrolmentid)) {
                $ueid = 0;
            } else {
                $ueid = $cert->enrolmentid;
            }
            $course = $cert->courseid;

            $data = array();
            $icon = '<img height="32px" title="'.get_string('pluginname', 'mod_ilddigitalcert').'"
              src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/blockchain-certificate.svg">';
            if (isset($cert->txhash)) {
                $icon = '<img height="32px" title="'.get_string('registered_and_signed', 'mod_ilddigitalcert').'"
                  src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/blockchain-block.svg">';
            }
            $data[] = $icon;
            $user = $DB->get_record_sql('select id, firstname, lastname from {user} where id = :id ',
              array('id' => $cert->userid));

            // TODO Zertifikat anzeigen.
            $data[] = \html_writer::link(
                $CFG->wwwroot.'/mod/ilddigitalcert/view.php?id='.
                  $cert->cmid.'&issuedid='.$cert->id.'&ueid='.$ueid,
                $cert->name);

            $data[] = \html_writer::link(
                new \moodle_url('/user/view.php?id='.
                $user->id.'&course='.$course.'&ueid='.$ueid), $user->firstname.' '.$user->lastname);
            $data[] = date('d.m.Y - H:i', $cert->timecreated);

            if($show_actions) {
                // TODO Zertifikat neu ausstellen.
                // Zertifikat in Blockchain speichern.
                if (!isset($cert->txhash)) {
                    $data[] = '<a class="myBtn" href="'. new \moodle_url($CFG->wwwroot.'/mod/ilddigitalcert/view.php?id='.$cert->id.'&ueid='.$ueid) . '">'.
                    get_string('toblockchain', 'mod_ilddigitalcert').'</a> '.
                    \html_writer::link(
                        new \moodle_url('/mod/ilddigitalcert/view.php?id='.
                        $cert->cmid.'&reissueid='.$cert->id.'&action=reissue'),
                        '<img alt="reissue certificate" title="reissue certificate"
                        src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/refresh_grey_24x24.png">');
                } else {
                    // TODO check revoked.
                    // TODO if not revoked: revoke certificate.
                    $data[] = '';
                    // TODO if revoked: unrevoke certificate.
                }
            }

            $table->data[] = $data;
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
    public static function get_message($name, $to_user, $subject, $html, $text) {
        global $DB, $OUTPUT, $CFG;

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

        // $message->contexturl = (new \moodle_url('/course/view.php?id=' . $this->get_context()->instanceid))->out(false); // A relevant URL for the notification
        // $message->contexturlname = 'To Course';

        return $message;
    }
}
