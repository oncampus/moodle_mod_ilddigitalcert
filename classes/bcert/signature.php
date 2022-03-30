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

namespace mod_ilddigitalcert\bcert;

/**
 * A signature object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class signature {


    /** @var string Blockchain address of the certifier. */
    private $address;

    /** @var string Email of the certifier. */
    private $email;

    /** @var string Given name of the cerifier. */
    private $givennames;

    /** @var string Surname of the certifier. */
    private $familyname;

    /** @var string Role of the certifier in his organization. */
    private $role;

    /** @var string Datetime of the moment the certificate was signed. */
    private $certificationdate;

    /** @var string Datetime of the moment the certificate was signed. Optional attribute. */
    private $certificationplace;

    /**
     * Getter for certificationdate attribute.
     *
     * @return string
     */
    public function get_certificationdate() {
        return $this->certificationdate;
    }

    /**
     * Constructor.
     */
    private function __construct() {
    }


    /**
     * Creates a signature object based on a given certifier.
     *
     * @param \core_user $certifier
     * @return signature
     */
    public static function new($certifier, $courseid) {
        $new = new self();
        $new->address = get_user_preferences('mod_ilddigitalcert_certifier', 'dummyaddress', $certifier);
        $new->email = $certifier->email;
        $new->givennames = $certifier->firstname;
        $new->familyname = $certifier->lastname;
        $new->role = manager::get_role_in_course($certifier->id, $courseid);
        $new->certificationdate = date('c', time());
        if (isset($certifier->city) and $certifier->city != '') {
            $new->certificationplace = $certifier->city; // TODO Is this correct?
        }
        return $new;
    }

    /**
     * Creates a signature Object based on an edci certificate.
     *
     * @param mySimpleXMLElement $xml Contains the signature information in edci format.
     * @return signature
     */
    public static function from_edci($xml) {
        if (!isset($xml->signature)) {
            return null;
        }
        $new = new self();
        $new->address = (string) $xml->signature->address;
        $new->email = str_replace('mailto:', '', $xml->signature->mailBox['uri']);
        $new->givennames = (string) $xml->signature->givenNames->text;
        $new->familyname = (string) $xml->signature->familyName->text;
        $new->role = (string) $xml->signature->role->text;
        $new->certificationdate = (string) $xml->signature->certificationDate;
        if (isset($xml->signature->certificationplace)) {
            $new->certificationplace = (string) $xml->signature->certificationPlace->text;
        }
        return $new;
    }

    /**
     * Creates a signature Object based on an openBadge certificate.
     *
     * @param \stdClass $json Contains the signature information in openBadge format.
     * @return signature
     */
    public static function from_ob($json) {
        if (!isset($json->{'extensions:signatureB4E'})) {
            return null;
        }
        $new = new self();
        $new->address = $json->{'extensions:signatureB4E'}->address;
        $new->email = $json->{'extensions:signatureB4E'}->email;
        $new->givennames = $json->{'extensions:signatureB4E'}->givenname;
        $new->familyname = $json->{'extensions:signatureB4E'}->surname;
        $new->role = $json->{'extensions:signatureB4E'}->role;
        $new->certificationdate = $json->{'extensions:signatureB4E'}->certificationdate;
        $new->certificationplace = manager::get_if_key_exists($json->{'extensions:signatureB4E'}, 'certificationplace');
        return $new;
    }

    /**
     * Returns a default Object containing signature data in openBadge format.
     *
     * @return \stdClass
     */
    public function get_ob() {
        $signature = new \stdClass();
        $signature->address = $this->address;
        $signature->email = $this->email;
        $signature->surname = $this->familyname;
        $signature->{'@context'} = \mod_ilddigitalcert\bcert\manager::CONTEXT_B4E_SIGNATURE;
        $signature->role = $this->role;
        $signature->certificationdate = $this->certificationdate;
        $signature->type = ["Extension", "SignatureB4E"];
        $signature->givenname = $this->givennames;
        if (isset($this->certificationplace)) {
            $signature->certificationplace = $this->certificationplace;
        }
        return $signature;
    }

    /**
     * Returns a mySimpleXMLElement containing signature data in edci format.
     *
     * @return mySimpleXMLElement
     */
    public function get_edci() {
        $root = mySimpleXMLElement::create_empty('signature');

        $root->addChild('address', $this->address);
        $mailbox = $root->addChild('mailBox');
        $mailbox->addAttribute('uri', 'mailto:' . $this->email);
        $root->addtextnode('givenNames', $this->givennames);
        $root->addtextnode('familyName', $this->familyname);
        $root->addtextnode('role', $this->role);
        $root->addChild('certificationDate', $this->certificationdate);
        if (isset($this->certificationplace)) {
            $root->addtextnode('certificationPlace', $this->certificationplace);
        }

        return $root;
    }
}
