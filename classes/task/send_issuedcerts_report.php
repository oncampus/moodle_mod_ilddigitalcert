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
 * Sends a report that should remind certifiers about recently issued certificates,
 * that are waiting to be signed and written to the blockchain.
 *
 * @package    mod_ilddigitalcert
 * @copyright  2022 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ilddigitalcert\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/ilddigitalcert/locallib.php');

/**
 * Sends a report that should remind certifiers about recently issued certificates,
 * taht are waiting to be signed and written to the blockchain.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2021, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_issuedcerts_report extends \core\task\scheduled_task {
    private const MESSAGE_NAME = 'ilddigitalcert_issuedcerts_report';

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('send_issuedcerts_report', 'mod_ilddigitalcert');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        // Get certificates that have been issued to the blockchain since the last report was sent.
        if(!$since = $DB->get_field('task_scheduled', 'lastruntime', array('classname' => '\mod_ilddigitalcert\task\send_issuedcerts_report'), IGNORE_MISSING)) {
            // Get certificates that have been issued to the blockchain in the last 24 hours.
            $time = new \DateTime("now", \core_date::get_user_timezone_object());
            $time->sub(new \DateInterval("P1D"));
            $since = $time->getTimestamp();
        }

        // Get issued certificates waiting to be signed.
        $issued_certificates_sql = "SELECT *
        FROM mdl_ilddigitalcert_issued
        WHERE txhash IS NULL
        AND timemodified >= ?
        ";
        $issued_certificates = $DB->get_records_sql(
            $issued_certificates_sql, 
            array($since)
        );

        if (empty($issued_certificates)) {
            // No need to send messages if there aren't any certificates to sign.
            return;
        }

        $courses = array();
        foreach($issued_certificates as $cert) {
            if(!in_array($cert->courseid, $courses)) {
                array_push($courses, $cert->courseid);
            }
        }

        // Get responsible certifiers.
        $certifiers = get_certifiers();

        // Send message to every certifier.
        foreach ($certifiers as $certifier) {
            if(!$message = $this->get_issued_certs_report($certifier, $issued_certificates)) continue;

            try {
                message_send($message);
            } catch (\moodle_exception $e) {
                print_r($e->getMessage());
            }
        }
        $teachers_and_courses = array();

        foreach($courses as $courseid) {
            // Get teachers of course with id $courseid
            $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
            $context = \context_course::instance($courseid);
            $courseteachers = get_role_users($role->id, $context);

            // Fill a dictionary with teachers as the key and the courses they are teaching as values.
            foreach($courseteachers as $teacher) {
                if(array_key_exists($teacher->id, $teachers_and_courses)) {
                    array_push($teachers_and_courses[$teacher->id], $courseid);
                } else {
                    $teachers_and_courses[$teacher->id] = array($courseid);
                }
            }
        }

        foreach($teachers_and_courses as $teacherid => $courseids) {
            // Skip teachers that are also certifiers.
            if(in_array($teacherid, $certifiers)) continue;


            if(!$message = $this->get_issued_certs_report($teacherid, $issued_certificates, $courseids)) continue;

            try {
                message_send($message);
            } catch (\moodle_exception $e) {
                print_r($e->getMessage());
            }
        }
    }

    private function get_issued_certs_report($userid, $issued_certificates, $courseids=null) {
        global $DB, $OUTPUT;
        $to_user = $DB->get_record("user", array('id' => $userid), '*', IGNORE_MISSING);

        $subject = (new \lang_string('issuedcerts_report:subject', 'mod_ilddigitalcert', null))->out($to_user->lang);
        $message_html = (new \lang_string('issuedcerts_report:intro', 'mod_ilddigitalcert', $to_user->firstname . " " . $to_user->lastname))->out($to_user->lang);

        // Set categorys for certs.
        $certids = array();
        $certs_responsible_for = array();
        $other_certs = $issued_certificates;

        // Get courses, for whom they are responsible.
        // Meaning ourses, they are enroled to and that have ilddigitalcert activities.
        $courses_responsible_for = null;
        if(isset($courseids)) {
            if(empty($courseids)) return null;
            
            list($insql, $inparams) = $DB->get_in_or_equal($courseids);
            $courses_responsible_for_sql = "SELECT c.id AS id, c.fullname AS fullname, c.shortname AS shortname, idc.name AS cert_name 
                FROM {course} c
                JOIN {ilddigitalcert} idc 
                    ON idc.course = c.id 
                WHERE c.id $insql;";
            $courses_responsible_for = $DB->get_records_sql($courses_responsible_for_sql, $inparams, IGNORE_MISSING);
        } else {
            $courses_responsible_for_sql = "SELECT e.courseid AS id, c.fullname AS fullname, c.shortname AS shortname, idc.name AS cert_name 
                FROM mdl_user_enrolments ue
                JOIN mdl_enrol e ON e.id = ue.enrolid
                JOIN mdl_course c ON c.id = e.courseid
                JOIN mdl_ilddigitalcert idc ON idc.course = e.courseid
                WHERE ue.userid = :userid;";
            $courses_responsible_for = $DB->get_records_sql($courses_responsible_for_sql, array('userid' => $userid), IGNORE_MISSING);
        }

        // Sort certs into categorys for every individual course.
        foreach ($courses_responsible_for as $course) {
            if (!$course->cert_name) {
                continue;
            }

            // Map certs to course.
            $certs_of_course = array();
            foreach ($issued_certificates as $id => $cert) {
                if ($cert->courseid == $course->id) {
                    $certs_of_course[] = $cert;
                    $certids[] = $id;
                    // Unset certs that fit into a category. So there are only certs left in $other_certs
                    // that didn't fit into another category.
                    unset($other_certs[$id]);
                }
            }

            if (!empty($certs_of_course)) {
                $certs_responsible_for[$course->id] = $certs_of_course;
            }
        }

        // Don't send message to this certifier if there are no issued certificates this certifier is responsible for.
        if(empty($certs_responsible_for)) {
            return null;
        }

        // Create tables for cert categories and add them to $message_html.
        if (!empty($certs_responsible_for)) {
            foreach ($certs_responsible_for as $courseid => $certs) {
                // Get Course name for 
                foreach ($courses_responsible_for as $c) {
                    if ($c->id == $courseid) {
                        $message_html .= '</br>';
                        $message_html .= '<h3>' . $c->fullname . ':</h3>';
                        break;
                    }
                }
                $message_html .= \mod_ilddigitalcert\manager::render_certs_table($certs, null, null, null, $to_user->lang);
                $message_html .= '</br>';

                if(isset($courseids) && empty(get_certifiers($courseid))) {
                    
                    $message_html .= $OUTPUT->pix_icon('i/warning', 'warning');
                    $message_html .= (new \lang_string('issuedcerts_report:nocertifierincourse', 'mod_ilddigitalcert', $c->fullname))->out($to_user->lang);
                }
            }
        }
        // Certs table for certs this certifier is not responsible for.
        // if (!empty($other_certs)) {
        //     if (!empty($certs_responsible_for)) {
        //         $message_html .= '<h3>' . (new \lang_string('issuedcerts_report:other_certs', 'mod_ilddigitalcert'))->out($to_user->lang) . '</h3>';
        //     }
        //     $message_html .= \mod_ilddigitalcert\manager::render_certs_table($other_certs, null, null, null, $to_user->lang);
        //     $message_html .= '</br>';
        // }

        $message_html .= (new \lang_string('issuedcerts_report:end', 'mod_ilddigitalcert'))->out($to_user->lang);

        // Create and send message.
        $message_text = \html_to_text($message_html);

        // Create contexturl.
        $contexturl = (new \moodle_url('/mod/ilddigitalcert/overview.php?cert_json=' . \json_encode($certids)))->out(false);
        $contexturlname = (new \lang_string('issuedcerts_report:contexturlname', 'mod_ilddigitalcert'))->out($to_user->lang);

        return \mod_ilddigitalcert\manager::get_message(self::MESSAGE_NAME, $to_user, $subject, $message_html, $message_text, $contexturl, $contexturlname);
    }
}
