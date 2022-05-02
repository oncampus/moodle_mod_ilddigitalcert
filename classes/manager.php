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

namespace mod_ilddigitalcert;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/ilddigitalcert/locallib.php');
require_once($CFG->dirroot . '/mod/ilddigitalcert/web3lib.php');

/**
 * Library of utility functions for mod_ilddigitalcert.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2022, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /**
     * Revokes a certificate and send an email to the former subject of the certificate.
     *
     * @param \stdClass $issuedcertificate Object that contains the certificate that should dbe revoked.
     * @param \core_user $certifier Moodle user/certifier that revokes the certificate.
     * @param string $pk private key of the certifier.
     *
     * @return bool Returns false if the cert couldn´t be revoked, else true.
     */
    public static function revoke($issuedcertificate, $certifier, $pk) {
        global $DB, $CFG, $SITE;

        $context = \context_module::instance($issuedcertificate->cmid);

        $pref = get_user_preferences('mod_ilddigitalcert_certifier', false, $certifier);
        if (!$pref) {
            \core\notification::error(get_string('not_a_certifier', 'mod_ilddigitalcert'));
            return false;
        } else {
            if ($pref != get_address_from_pk($pk)) {
                \core\notification::error(get_string('wrong_private_key', 'mod_ilddigitalcert'));
                return false;
            }
        }

        if (!isset($issuedcertificate->certhash)) {
            return false;
        }

        // If revocation successful.
        if (!revoke_certificate($issuedcertificate->certhash, $pk)) {
            return false;
        }

        // Unset metadata that describes properties of a signed certifiacte.
        $metadata = $issuedcertificate->metadata;
        $metadata = json_decode($metadata);
        unset($metadata->{'extensions:institutionTokenILD'});
        unset($metadata->{'extensions:contractB4E'});
        unset($metadata->{'extensions:institutionTokenILD'});
        unset($metadata->verification->{'extensions:verifyB4E'});

        $json = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        // Save hashes in db issued.
        $issuedcertificate->inblockchain = false;
        $issuedcertificate->certhash = null;
        $issuedcertificate->txhash = null;
        $issuedcertificate->metadata = $json;
        $issuedcertificate->institution_token = null;
        $issuedcertificate->edci = null;

        $DB->update_record('ilddigitalcert_issued', $issuedcertificate);

        // Log certificate_registered event.
        $event = \mod_ilddigitalcert\event\certificate_revoked::create(
            array(
                'context' => $context,
                'objectid' => $issuedcertificate->id,
                'userid' => $certifier->id,
                'relateduserid' => $issuedcertificate->userid,
            )
        );
        $event->trigger();

        if ($receiver = $DB->get_record('user', array('id' => $issuedcertificate->userid))) {
            // Email to user.
            $fromuser = \core_user::get_support_user();
            $fullname = explode(' ', get_string('modulenameplural', 'mod_ilddigitalcert'));
            $fromuser->firstname = $fullname[0];
            $fromuser->lastname = $fullname[1];
            $subject = get_string('subject_certificate_revoked', 'mod_ilddigitalcert');
            $a = new \stdClass();
            $a->fullname = $receiver->firstname . ' ' . $receiver->lastname;
            $a->url = $CFG->wwwroot . '/mod/ilddigitalcert/view.php?id=' . $issuedcertificate->cmid;
            $a->from = $SITE->fullname;
            $messagehtml = get_string('message_certificate_revoked', 'mod_ilddigitalcert', $a);
            $message = html_to_text($messagehtml);
            email_to_user($receiver, $fromuser, $subject, $message, $messagehtml);
        }
        return true;
    }


    /**
     * Renders the given certificates array as a html table.
     *
     * @param array $certificates Certificates to be listed in the table.
     * @param int $course
     * @return string HTML representation of a certificates table.
     */
    public static function render_certs_table($certificates, $courseid = null, $userid = null, $lang = null) {
        global $CFG, $DB;

        if (!$certificates || empty($certificates)) {
            return '';
        }

        // Create certificates table.
        $table = new \html_table();
        $table->attributes['class'] = 'generaltable m-element-certs-table';

        $head[] = (new \lang_string('status'))->out($lang);
        $colclasses[] = 'col-status';
        $align[] = 'center';
        $head[] = (new \lang_string('title', 'mod_ilddigitalcert'))->out($lang);
        $colclasses[] = 'col-title';
        $align[] = 'left';

        if (!\is_scalar($userid)) {
            $head[] = (new \lang_string('recipient', 'mod_ilddigitalcert'))->out($lang);
            $colclasses[] = 'col-recipient';
            $align[] = 'left';
        }
        if (!\is_scalar($courseid)) {
            $head[] = (new \lang_string('course'))->out($lang);
            $colclasses[] = 'col-course';
            $align[] = 'left';
        }

        $head[] = (new \lang_string('startdate', 'mod_ilddigitalcert'))->out($lang);
        $colclasses[] = 'col-startdate';
        $align[] = 'left';

        $table->head = $head;
        $table->colclasses = $colclasses;
        $table->align = $align;

        // Fill table.
        foreach ($certificates as $certificate) {
            $row = array();
            $icon = '<img height="32px" title="'
                . (new \lang_string('pluginname', 'mod_ilddigitalcert'))->out($lang)
                . '"src="' . $CFG->wwwroot . '/mod/ilddigitalcert/pix/blockchain-certificate.svg">';
            if (isset($certificate->txhash)) {
                $icon = '<img height="32px" title="'
                    . (new \lang_string('registered_and_signed', 'mod_ilddigitalcert'))->out($lang)
                    . '"src="' . $CFG->wwwroot . '/mod/ilddigitalcert/pix/blockchain-block.svg">';
            }
            $row[] = $icon;

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
     * @return \core\message\message Message object.
     */
    public static function get_message($name, $touser, $subject, $html, $text, $contexturl = null, $contexturlname = null) {

        $message = new \core\message\message();
        $message->component = 'mod_ilddigitalcert';
        $message->name = $name;
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $touser;
        $message->subject = $subject;
        $message->fullmessageformat = FORMAT_HTML;

        $message->fullmessagehtml = $html;

        $message->fullmessage = $text;

        $message->notification = 1;

        if ($contexturl) {
            $message->contexturl = $contexturl;
        }
        if ($contexturlname) {
            $message->contexturlname = $contexturlname;
        }

        return $message;
    }
}
