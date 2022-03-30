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
 * Sends a report that informs certifiers about certificates that have been signed and
 * written to  the blockchain automatically since the last report.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2022, Pascal Hürten <pascal.huerten@th-luebeck.de>
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
        $autocourses = $DB->get_records(
            "ilddigitalcert",
            array("automation" => 1, "automation_report" => 1),
            null,
            "course",
            IGNORE_MISSING
        );
        if (!$autocourses) {
            return;
        }

        $autocourses = array_keys($autocourses);

        list($insql, $inparams) = $DB->get_in_or_equal($autocourses);

        // Get certificates that have been registered in the blockchain since the last report was sent.
        $since = $DB->get_field(
            'task_scheduled',
            'lastruntime',
            array('classname' => '\mod_ilddigitalcert\task\send_automation_report'),
            IGNORE_MISSING
        );
        if (!$since) {
            // Get certificates that have been automatically written to the blockchain in the last week.
            $time = new \DateTime("now", \core_date::get_user_timezone_object());
            $time->sub(new \DateInterval("P7D"));
            $since = $time->getTimestamp();
        }

        $issuedcertificatessql = "SELECT *
            FROM mdl_ilddigitalcert_issued
            WHERE txhash IS NOT NULL
            AND timemodified >= ?
            AND courseid $insql;";
        $issuedcertificates = $DB->get_records_sql($issuedcertificatessql, array_merge(array($since), $inparams), IGNORE_MISSING);
        if (empty($issuedcertificates)) {
            // No need to send messages if there aren't any certificates to sign.
            return;
        }

        // Get responsible certifiers.
        $certifiers = get_certifiers();

        // Send message to every certifier.
        foreach ($certifiers as $certifier) {
            $touser = $DB->get_record("user", array('id' => $certifier), '*', IGNORE_MISSING);

            $subject = (new \lang_string('automation_report:subject', 'mod_ilddigitalcert', null))->out($touser->lang);
            $messagehtml = (new \lang_string(
                'automation_report:intro',
                'mod_ilddigitalcert',
                $touser->firstname . " " . $touser->lastname)
            )->out($touser->lang);

            // Set categorys for certs.
            $certids = array();
            $certsresponsiblefor = array();
            $othercerts = $issuedcertificates;

            // Get courses, for whome they are responsible.
            // Theese are courses, they are enroled to and that have ilddigitalcert activities.
            list($insql, $inparams) = $DB->get_in_or_equal($autocourses);
            $coursesresponsibleforsql = "SELECT c.id AS id, c.fullname AS fullname, c.shortname AS shortname, idc.name AS cert_name
                FROM {course} c
                JOIN {ilddigitalcert} idc
                    ON idc.course = c.id
                WHERE c.id $insql
                AND auto_certifier = ?;";
            $coursesresponsiblefor = $DB->get_records_sql(
                $coursesresponsibleforsql,
                array_merge($inparams, array($certifier)),
                IGNORE_MISSING
            );

            // Sort certs into categorys for every individual course.
            foreach ($coursesresponsiblefor as $course) {
                if (!$course->cert_name) {
                    continue;
                }

                // Map certs to course.
                $certsofcourse = array();
                foreach ($issuedcertificates as $id => $cert) {
                    // Only choose certificates that were issued after the last message, to prevent duplicate notifications.
                    if ($cert->courseid != $course->id) {
                        continue;
                    }

                    $certsofcourse[] = $cert;
                    $certids[] = $id;
                    // Unset certs that fit into a category. So there are only certs left in $other_certs
                    // that didn't fit into another category.
                    unset($othercerts[$id]);
                }

                if (!empty($certsofcourse)) {
                    $certsresponsiblefor[$course->id] = $certsofcourse;
                }
            }

            // Create tables for cert categories and add them to $message_html.
            if (!empty($certsresponsiblefor)) {
                foreach ($certsresponsiblefor as $course => $certs) {
                    foreach ($coursesresponsiblefor as $c) {
                        if ($c->id == $course) {
                            $messagehtml .= '</br>';
                            $messagehtml .= '<h3>' . $c->fullname . ':</h3>';
                            break;
                        }
                    }
                    $messagehtml .= \mod_ilddigitalcert\manager::render_certs_table($certs, null, null, $touser->lang);
                    $messagehtml .= '</br>';
                }
            }
            if (!empty($othercerts)) {
                $messagehtml .= '<h3>';
                $messagehtml .= (new \lang_string('automation_report:other_certs', 'mod_ilddigitalcert'))->out($touser->lang);
                $messagehtml .= '</h3>';
                $messagehtml .= \mod_ilddigitalcert\manager::render_certs_table($othercerts, null, null, $touser->lang);
                $messagehtml .= '</br>';
            }
            $messagehtml .= (new \lang_string('automation_report:end', 'mod_ilddigitalcert'))->out($touser->lang);

            $messagetext = \html_to_text($messagehtml);

            // Create contexturl.
            $contexturl = (new \moodle_url('/mod/ilddigitalcert/overview.php?cert_json=' . \json_encode($certids)))->out(false);
            $contexturlname = (new \lang_string('automation_report:contexturlname', 'mod_ilddigitalcert'))->out($touser->lang);

            $message = \mod_ilddigitalcert\manager::get_message(
                self::MESSAGE_NAME,
                $touser,
                $subject,
                $messagehtml,
                $messagetext,
                $contexturl,
                $contexturlname
            );
            try {
                $messageid = message_send($message);
            } catch (\moodle_exception $e) {
                \core\notification::error($e->getMessage());
            }
        }
    }
}
