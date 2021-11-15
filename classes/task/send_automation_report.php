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
 * Sends a report that informs certifiers about certificats that have been signed and
 * written to  the blockchain automatically since the last report.
 *
 * @package    mod_ilddigitalcert
 * @copyright  2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ilddigitalcert\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/ilddigitalcert/locallib.php');

/**
 * Sends a report that informs certifiers about certificats that have been signed and
 * written to  the blockchain automatically since the last report.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2021, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_automation_report extends \core\task\scheduled_task {
    private const MESSAGE_NAME = 'ilddigitalcert_automation_report';

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('send_automation_report', 'mod_ilddigitalcert');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        // Get courses where automation is enabled.
        $auto_courses = $DB->get_record("ilddigitalcert", array("automation" => 1), "course", IGNORE_MISSING);
        print_r("        auto_courses: ");
        print_r($auto_courses);
        $courses = [];
        foreach ($auto_courses as $c) {
            $courses[] = $c;
        }
        list($insql, $inparams) = $DB->get_in_or_equal($courses);

        // Get certificats that have been automatically written to the blockchain in the last 24 hours.
        $time = new \DateTime("now", \core_date::get_user_timezone_object());
        $time->sub(new \DateInterval("P1D"));
        $since = $time->getTimestamp();

        $issued_certificats_sql = "SELECT *
            FROM mdl_ilddigitalcert_issued
            WHERE txhash IS NOT NULL
            AND timemodified > ?
            AND courseid $insql;";
        $issued_certificats = $DB->get_records_sql($issued_certificats_sql, array_merge(array($since), $inparams), IGNORE_MISSING);
        print_r("        issued_certificats: ");
        print_r($issued_certificats);
        if(empty($issued_certificats)) {
            // No need to send messages if there aren't any certificats to sign.
            return;
        }

        // Get responsible certifiers.
        $certifiers = get_certifiers();
        print_r("       certifiers: ");
        print_r($certifiers);
        $subject = \get_string('automation_report:subject', 'mod_ilddigitalcert');

        // Send message to every certifier.
        foreach($certifiers as $certifier) {
            $last_message_sql = "SELECT MAX(timecreated)
                FROM mdl_notifications
                WHERE useridto = :userid
                AND eventtype = 'ilddigitalcert_automation_report';";
            $last_message = $DB->get_record_sql($last_message_sql, array("userid" => $certifier), IGNORE_MISSING);
            $to_user = $DB->get_record("user", array('id' => $certifier), '*', IGNORE_MISSING);

            $message_html = \get_string('automation_report:intro', 'mod_ilddigitalcert', $to_user->firstname . " " . $to_user->lastname);

            // Set categorys for certs.
            $certs_responsible_for = array();
            $other_certs = $issued_certificats;

            // Get courses, for whom they are responsible.
            // Meaning ourses, they are enroled to and that have ilddigitalcert activities.
            $courses_responsible_for_sql = "SELECT e.courseid AS id, c.fullname AS fullname, c.shortname AS shortname, idc.name AS cert_name FROM mdl_user_enrolments ue
            JOIN mdl_enrol e ON e.id = ue.enrolid
            JOIN mdl_ilddigitalcert idc ON idc.course = e.courseid
            JOIN mdl_course c ON c.id = e.courseid
            WHERE ue.userid = :userid;";
            $courses_responsible_for = $DB->get_records_sql($courses_responsible_for_sql, array('userid' => $certifier), IGNORE_MISSING);
            print_r("       courses_responsible_for: ");
            print_r($courses_responsible_for);

            // Sort certs into categorys for every individual course.
            foreach($courses_responsible_for as $course) {
                if(!$course->cert_name) {
                    continue;
                }

                // Map certs to course.
                $certs_of_course = array();
                foreach($issued_certificats as $key => $cert) {
                    if($cert->courseid == $course->id) {
                        $certs_of_course[] = $cert;
                        // Unset certs that fit into a category. So there are only certs left in $other_certs
                        // that didn't fit into another category.
                        unset($other_certs[$key]);
                    }
                }

                if(!empty($certs_of_course)) {
                    $certs_responsible_for[$course->id] = $certs_of_course;
                }
            }

            // Create tables for cert categories and add them to $message_html.
            if(!empty($certs_responsible_for)) {
                foreach($certs_responsible_for as $course => $certs) {
                    foreach($courses_responsible_for as $c) {
                        if($c->id == $course) {
                            $message_html .= '</br>';
                            $message_html .= '<h3>' . $c->fullname . ':</h3>';
                            break;
                        }
                    }
                    $message_html .= \mod_ilddigitalcert\manager::get_certs_table($certs, false);
                    $message_html .= '</br>';
                }
            }
            if(!empty($other_certs)) {
                $message_html .= '<h3>' . \get_string('automation_report:other_certs', 'mod_ilddigitalcert') . '</h3>';
                $message_html .= \mod_ilddigitalcert\manager::get_certs_table($other_certs, false);
                $message_html .= '</br>';
            }
            $message_html .= \get_string('automation_report:end', 'mod_ilddigitalcert');

            $message_text = \html_to_text($message_html);

            // Create and send message.
            $message = \mod_ilddigitalcert\manager::get_message(self::MESSAGE_NAME, $to_user, $subject, $message_html, $message_text);
            try{
                $messageid = message_send($message);
            } catch (\moodle_exception $e) {
                print_r($e->getMessage());
            }
            if($messageid) {
                print_r("       sent successfull: ");
                print_r($message_text);
            } else {
                print_r("       could'nt send message: ");
            }
        }
    }
}