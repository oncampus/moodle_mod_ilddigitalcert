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

use mod_ilddigitalcert\web3_manager;
use mod_ilddigitalcert\bcert\certificate;

/**
 * Library of funtions for managing certificates.
 *
 * @package   mod_ilddigitalcert
 * @copyright 2022, Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_manager {
    /**
     * Stores a hash of an openBadge certificate in the clockchain.
     * Before calculating the hash the signature and institution token has to be added to the certificate.
     *
     * @param stdClass $issuedcertificate stdClass that contains the data that needs to be stored in the bc.
     * @param core_user $certifier Moodle user that signs the certificate.
     * @param string $pk private key of the certifier.
     * @return bool Returns false if the cert couldn´t be written to the blockchain.
     */
    public static function to_blockchain($issuedcertificate, $certifier, $pk) {
        global $DB, $SITE;

        $context = \context_module::instance($issuedcertificate->cmid);
        $pref = get_user_preferences('mod_ilddigitalcert_certifier', false, $certifier);
        if (!$pref) {
            throw new \moodle_exception('not_a_certifier', 'mod_ilddigitalcert');
        } else {
            if ($pref != web3_manager::get_address_from_pk($pk)) {
                \core\notification::error(get_string('wrong_private_key', 'mod_ilddigitalcert'));
                return false;
            }
        }

        if (isset($issuedcertificate->txhash)) {
            return false;
        }

        $metacertificate = certificate::from_ob($issuedcertificate->metadata);

        // Add signature.
        $metacertificate->sign($certifier, $issuedcertificate->courseid);

        // Save salt/token to file.
        if (!$tokenid = save_token()) {
            $tokenid = 'error';
        }
        $salt = get_token($tokenid);

        // Calculate hash with $salt.
        $hash = $metacertificate->get_ob_hash($salt);

        $startdate = strtotime($metacertificate->get_issuedon());
        if ($metacertificate->get_validuntil() !== null) {
            $enddate = strtotime($metacertificate->get_validuntil());
        } else {
            if (get_config('mod_ilddigitalcert', 'demo_mode')) {
                $enddate = 9999999999;
            } else {
                $enddate = 0;
            }
        }
        if ($enddate != 0 and $enddate <= $startdate) {
            \core\notification::error('Certificate endate cannot be before the stardate.');
            return false;
        }

        $hashes = web3_manager::store_certificate($hash, $startdate, $enddate, $pk);
        if ($hashes) {
            // Add verification.
            $metacertificate->add_verification($hash);
            // Save hashes in db issued.
            $issuedcertificate->inblockchain = true;
            $issuedcertificate->certhash = $hashes->certhash;
            $issuedcertificate->txhash = $hashes->txhash;
            $issuedcertificate->metadata = $metacertificate->get_ob();
            $issuedcertificate->edci = $metacertificate->get_edci();
            $issuedcertificate->institution_token = $tokenid;

            // Update db record.
            $DB->update_record('ilddigitalcert_issued', $issuedcertificate);

            // Log certificate_registered event.
            $event = \mod_ilddigitalcert\event\certificate_registered::create(
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
                $subject = get_string('subject_new_digital_certificate', 'mod_ilddigitalcert');
                $a = new \stdClass();
                $a->fullname = $receiver->firstname . ' ' . $receiver->lastname;
                $a->url = (new \moodle_url('/mod/ilddigitalcert/view.php', array('id' => $issuedcertificate->cmid)))->out();
                $a->from = $SITE->fullname;
                $messagehtml = get_string('message_new_digital_certificate_html', 'mod_ilddigitalcert', $a);
                $message = html_to_text($messagehtml);
                email_to_user($receiver, $fromuser, $subject, $message, $messagehtml);
            }
            return true;
        }
        return false;
    }

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
            if ($pref != web3_manager::get_address_from_pk($pk)) {
                \core\notification::error(get_string('wrong_private_key', 'mod_ilddigitalcert'));
                return false;
            }
        }

        if (!isset($issuedcertificate->certhash)) {
            return false;
        }

        // Revoke certificate.
        if (!web3_manager::revoke_certificate($issuedcertificate->certhash, $pk)) {
            // Return if revocation not successfull.
            return false;
        }

        // Unset metadata that describes properties of a signed certifiacte.
        $metadata = $issuedcertificate->metadata;
        $metadata = json_decode($metadata);
        unset($metadata->{'extensions:institutionTokenILD'});
        unset($metadata->{'extensions:contractB4E'});
        // unset($metadata->{'extensions:signatureB4E'});
        unset($metadata->verification);

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
}
