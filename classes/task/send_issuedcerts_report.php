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
 * @copyright  2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
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

        // Get issued certificates waiting to be signed.
        $issued_certificates = $DB->get_records('ilddigitalcert_issued', array('inblockchain' => false));
        print_r("        issued_certificates: ");
        print_r($issued_certificates);
        if (empty($issued_certificates)) {
            // No need to send messages if there aren't any certificates to sign.
            return;
        }

        // Get responsible certifiers.
        $certifiers = get_certifiers();
        print_r("       certifiers: ");
        print_r($certifiers);
        $subject = \get_string('issuedcerts_report:subject', 'mod_ilddigitalcert');

        // Send message to every certifier.
        foreach ($certifiers as $certifier) {
            $to_user = $DB->get_record("user", array('id' => $certifier), '*', IGNORE_MISSING);

            $message_html = \get_string('issuedcerts_report:intro', 'mod_ilddigitalcert', $to_user->firstname . " " . $to_user->lastname);

            // Set categorys for certs.
            $certs_responsible_for = array();
            $other_certs = $issued_certificates;

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
            foreach ($courses_responsible_for as $course) {
                if (!$course->cert_name) {
                    continue;
                }

                // Map certs to course.
                $certs_of_course = array();
                $certids = array();
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

            // Create tables for cert categories and add them to $message_html.
            if (!empty($certs_responsible_for)) {
                foreach ($certs_responsible_for as $course => $certs) {
                    foreach ($courses_responsible_for as $c) {
                        if ($c->id == $course) {
                            $message_html .= '</br>';
                            $message_html .= '<h3>' . $c->fullname . ':</h3>';
                            break;
                        }
                    }
                    $message_html .= \mod_ilddigitalcert\manager::render_certs_table($certs);
                    $message_html .= '</br>';
                }
            }
            if (!empty($other_certs)) {
                if (!empty($certs_responsible_for)) {
                    $message_html .= '<h3>' . \get_string('issuedcerts_report:other_certs', 'mod_ilddigitalcert') . '</h3>';
                }
                $message_html .= \mod_ilddigitalcert\manager::render_certs_table($other_certs);
                $message_html .= '</br>';
            }
            $message_html .= \get_string('issuedcerts_report:end', 'mod_ilddigitalcert');

            // Create and send message.
            $message_text = \html_to_text($message_html);

            // Create contexturl.
            $contexturl = (new \moodle_url('/mod/ilddigitalcert/certifier_overview.php?cert_json=' . \json_encode($certids)))->out(false);
            $contexturlname = 'Sign issued certificates';

            $message = \mod_ilddigitalcert\manager::get_message(self::MESSAGE_NAME, $to_user, $subject, $message_html, $message_text, $contexturl, $contexturlname);
            try {
                $messageid = message_send($message);
            } catch (\moodle_exception $e) {
                print_r($e->getMessage());
            }
            if ($messageid) {
                print_r("       sent successfull: ");
                print_r($message_text);
            } else {
                print_r("       could'nt send message: ");
            }
        }
    }
}
