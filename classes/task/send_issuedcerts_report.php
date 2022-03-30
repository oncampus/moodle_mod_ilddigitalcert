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

namespace mod_ilddigitalcert\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/ilddigitalcert/locallib.php');

/**
 * Sends a report that should remind certifiers about recently issued certificates,
 * taht are waiting to be signed and written to the blockchain.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2022, Pascal Hürten <pascal.huerten@th-luebeck.de>
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
        $since = $DB->get_field(
            'task_scheduled',
            'lastruntime',
            array('classname' => '\mod_ilddigitalcert\task\send_issuedcerts_report'),
            IGNORE_MISSING
        );
        if (!$since) {
            // Get certificates that have been issued to the blockchain in the last 24 hours.
            $time = new \DateTime("now", \core_date::get_user_timezone_object());
            $time->sub(new \DateInterval("P1D"));
            $since = $time->getTimestamp();
        }

        // Get issued certificates waiting to be signed.
        $issuedcertificatessql = "SELECT *
        FROM mdl_ilddigitalcert_issued
        WHERE txhash IS NULL
        AND timemodified >= ?
        ";
        $issuedcertificates = $DB->get_records_sql(
            $issuedcertificatessql,
            array($since)
        );

        if (empty($issuedcertificates)) {
            // No need to send messages if there aren't any certificates to sign.
            return;
        }

        $courses = array();
        foreach ($issuedcertificates as $cert) {
            if (!in_array($cert->courseid, $courses)) {
                array_push($courses, $cert->courseid);
            }
        }

        // Get responsible certifiers.
        $certifiers = get_certifiers();

        // Send message to every certifier.
        foreach ($certifiers as $certifier) {
            if (!$message = $this->get_issued_certs_report($certifier, $issuedcertificates)) {
                continue;
            }

            try {
                message_send($message);
            } catch (\moodle_exception $e) {
                \core\notification::error($e->getMessage());
            }
        }
        $teachersandcourses = array();

        foreach ($courses as $courseid) {
            // Get teachers of course with id $courseid.
            $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
            $context = \context_course::instance($courseid);
            $courseteachers = get_role_users($role->id, $context);

            // Fill a dictionary with teachers as the key and the courses they are teaching as values.
            foreach ($courseteachers as $teacher) {
                if (array_key_exists($teacher->id, $teachersandcourses)) {
                    array_push($teachersandcourses[$teacher->id], $courseid);
                } else {
                    $teachersandcourses[$teacher->id] = array($courseid);
                }
            }
        }

        foreach ($teachersandcourses as $teacherid => $courseids) {
            // Skip teachers that are also certifiers.
            if (in_array($teacherid, $certifiers)) {
                continue;
            }

            if (!$message = $this->get_issued_certs_report($teacherid, $issuedcertificates, $courseids)) {
                continue;
            }

            try {
                message_send($message);
            } catch (\moodle_exception $e) {
                \core\notification::error($e->getMessage());
            }
        }
    }

    private function get_issued_certs_report($userid, $issuedcertificates, $courseids=null) {
        global $DB, $OUTPUT;
        $touser = $DB->get_record("user", array('id' => $userid), '*', IGNORE_MISSING);

        $subject = (new \lang_string('issuedcerts_report:subject', 'mod_ilddigitalcert', null))->out($touser->lang);
        $messagehtml = (new \lang_string(
            'issuedcerts_report:intro',
            'mod_ilddigitalcert',
            $touser->firstname . " " . $touser->lastname)
        )->out($touser->lang);

        // Set categorys for certs.
        $certids = array();
        $certsresponsiblefor = array();
        $othercerts = $issuedcertificates;

        // Get courses, for whom they are responsible.
        // Meaning ourses, they are enroled to and that have ilddigitalcert activities.
        $coursesresponsiblefor = null;
        if (isset($courseids)) {
            if (empty($courseids)) {
                return null;
            }

            list($insql, $inparams) = $DB->get_in_or_equal($courseids);
            $responsibleforsql = "SELECT c.id AS id, c.fullname AS fullname, c.shortname AS shortname, idc.name AS cert_name
                FROM {course} c
                JOIN {ilddigitalcert} idc
                    ON idc.course = c.id
                WHERE c.id $insql;";
            $coursesresponsiblefor = $DB->get_records_sql($responsibleforsql, $inparams, IGNORE_MISSING);
        } else {
            $responsibleforsql = "SELECT e.courseid AS id, c.fullname AS fullname, c.shortname AS shortname, idc.name AS cert_name
                FROM mdl_user_enrolments ue
                JOIN mdl_enrol e ON e.id = ue.enrolid
                JOIN mdl_course c ON c.id = e.courseid
                JOIN mdl_ilddigitalcert idc ON idc.course = e.courseid
                WHERE ue.userid = :userid;";
            $coursesresponsiblefor = $DB->get_records_sql($responsibleforsql, array('userid' => $userid), IGNORE_MISSING);
        }

        // Sort certs into categorys for every individual course.
        foreach ($coursesresponsiblefor as $course) {
            if (!$course->cert_name) {
                continue;
            }

            // Map certs to course.
            $certsofcourse = array();
            foreach ($issuedcertificates as $id => $cert) {
                if ($cert->courseid == $course->id) {
                    $certsofcourse[] = $cert;
                    $certids[] = $id;
                    // Unset certs that fit into a category. So there are only certs left in $other_certs
                    // that didn't fit into another category.
                    unset($othercerts[$id]);
                }
            }

            if (!empty($certsofcourse)) {
                $certsresponsiblefor[$course->id] = $certsofcourse;
            }
        }

        // Don't send message to this certifier if there are no issued certificates this certifier is responsible for.
        if (empty($certsresponsiblefor)) {
            return null;
        }

        // Create tables for cert categories and add them to $message_html.
        if (!empty($certsresponsiblefor)) {
            foreach ($certsresponsiblefor as $courseid => $certs) {
                // Get course name for table heading.
                foreach ($coursesresponsiblefor as $c) {
                    if ($c->id == $courseid) {
                        $messagehtml .= '</br>';
                        $messagehtml .= '<h3>' . $c->fullname . ':</h3>';
                        break;
                    }
                }
                $messagehtml .= \mod_ilddigitalcert\manager::render_certs_table($certs, null, null, $touser->lang);
                $messagehtml .= '</br>';

                if (isset($courseids) && empty(get_certifiers($courseid))) {

                    $messagehtml .= $OUTPUT->pix_icon('i/warning', 'warning');
                    $messagehtml .= (new \lang_string(
                        'issuedcerts_report:nocertifierincourse',
                        'mod_ilddigitalcert',
                        $c->fullname
                    ))->out($touser->lang);
                }
            }
        }

        $messagehtml .= (new \lang_string('issuedcerts_report:end', 'mod_ilddigitalcert'))->out($touser->lang);

        // Create and send message.
        $messagetext = \html_to_text($messagehtml);

        // Create contexturl.
        $contexturl = (new \moodle_url('/mod/ilddigitalcert/overview.php?cert_json=' . \json_encode($certids)))->out(false);
        $contexturlname = (new \lang_string('issuedcerts_report:contexturlname', 'mod_ilddigitalcert'))->out($touser->lang);

        return \mod_ilddigitalcert\manager::get_message(
            self::MESSAGE_NAME,
            $touser,
            $subject,
            $messagehtml,
            $messagetext,
            $contexturl,
            $contexturlname
        );
    }
}
