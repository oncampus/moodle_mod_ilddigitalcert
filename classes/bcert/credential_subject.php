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
 * A credential_subject object represents data about the the certificate holder
 * that is essential for both openbadge and edci certificats
 * and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class credential_subject {

    /** @var string Unique identifier for use in edci. */
    private $identifier;

    /**  @var string Email. */
    private $email;

    /** @var string Given names. */
    private $givennames;

    /** @var string Family name. */
    private $familyname;

    /** @var array Achievements. */
    private $achievements = [];

    /**
     * Getter for $this->identifier.
     *
     * @return string
     */
    public function get_identifier() {
        return $this->identifier;
    }

    /**
     * Getter for $this->givennames.
     *
     * @return string
     */
    public function get_givennames() {
        return $this->givennames;
    }

    /**
     * Getter for $this->familyname.
     *
     * @return string
     */
    public function get_familyname() {
        return $this->familyname;
    }

    /**
     * Getter for $this->email.
     *
     * @return string
     */
    public function get_email() {
        return $this->email;
    }

    /**
     * Constructor.
     */
    private function __construct() {
    }

    /**
     * Creates a credential_subject object, based on a given moodle user and achieved competences.
     *
     * @param certificate $cert
     * @param \core_user $subject
     * @param array $expertise
     * @return void
     */
    public static function new($cert, $subject, $expertise) {
        $new = new self();
        $new->identifier = $subject->id;
        $new->email = $subject->email;
        $new->givennames = $subject->firstname;
        $new->familyname = $subject->lastname;
        foreach ($expertise as $exp) {
            $new->achievements[] = achievement::new($cert, $exp);
        }
        return $new;
    }

    /**
     * Creates a credential_subject Object based on an edci certificate.
     *
     * @param certificate $cert certificate that references this object.
     * @param mySimpleXMLElement $xml Contains the credential subject information in edci format.
     * @return credential_subject
     */
    public static function from_edci($cert, $xml) {
        $new = new credential_subject();
        $data = $xml->credentialSubject;
        $new->identifier = (string) $data->identifier;
        $new->email = str_replace('mailto:', '', $data->contactPoint->mailBox['uri']);
        $new->givennames = (string) $data->givenNames->text;
        $new->familyname = (string) $data->familyName->text;

        foreach ($data->achievements->learningAchievement as $learningachievement) {
            $new->achievements[] = achievement::from_edci($cert, $learningachievement);
        }

        return $new;
    }

    /**
     * Creates a credential_subject Object based on an openBadge certificate.
     *
     * @param certificate $cert certificate that references this object.
     * @param \stdClass $json Contains the credential subject information in openBadge format.
     * @return credential_subject
     */
    public static function from_ob($cert, $json) {
        $new = new credential_subject();
        $new->identifier = $json->{'extensions:recipientB4E'}->reference;
        $new->email = $json->{'extensions:recipientB4E'}->email;
        $new->givennames = $json->{'extensions:recipientB4E'}->givenname;
        $new->familyname = $json->{'extensions:recipientB4E'}->surname;

        $expertises = $json->badge->{'extensions:badgeexpertiseB4E'}->expertise;
        foreach ($expertises as $expertise) {
            $new->achievements[] = achievement::from_ob($cert, $expertise);
        }
        return $new;
    }

    /**
     * Creates a default object that contains info about the expertises of a credential subject
     * by gathering data from its achievements for use in an openBadge cert.
     *
     * @return \stdClass
     */
    public function get_expertise() {
        // Get expertises from achievements.
        $expertise = [];
        foreach ($this->achievements as $achievement) {
            array_push($expertise, $achievement->get_expertise());
        }

        return (object) [
            '@context' => \mod_ilddigitalcert\bcert\manager::CONTEXT_B4E_BADGEEXPERTISE,
            'type' => ["Extension", "BadgeExpertiseB4E"],
            'expertise' => $expertise
        ];
    }

    /**
     * Returns a default Object containing credential subject data in openBadge format.
     *
     * @return \stdClass
     */
    public function get_ob() {
        $recipient = new \stdClass();
        $recipient->reference = $this->identifier;
        $recipient->email = $this->email;
        $recipient->{'@context'} = \mod_ilddigitalcert\bcert\manager::CONTEXT_B4E_RECIPIENT;
        $recipient->type = ["Extension", "RecipientB4E"];
        $recipient->givenname = $this->givennames;
        $recipient->surname = $this->familyname;
        return $recipient;
    }

    /**
     * Returns a mySimpleXMLElement containing credential subject data in edci format.
     *
     * @return mySimpleXMLElement
     */
    public function get_edci() {
        $root = mySimpleXMLElement::create_empty('credentialSubject');
        $root->addAttribute('id', 'urn:bcert:person:1');
        $root->addChild('identifier', $this->identifier);
        $contactpoint = $root->addChild('contactPoint');
        $mailbox = $contactpoint->addChild('mailBox');
        $mailbox->addAttribute('uri', 'mailto:' . $this->email);
        $root->addtextnode('givenNames', $this->givennames);
        $root->addtextnode('familyName', $this->familyname);

        $achievementsnode = $root->addChild('achievements');
        foreach ($this->achievements as $achievement) {
            $achievementsnode->appendXML($achievement->get_edci());
        }

        return $root;
    }
}
