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


defined('MOODLE_INTERNAL') || die();

require_once('achievement.php');
require_once('mySimpleXMLElement.php');

/**
 * A CredentialSubject object represents data about the the certificate holder
 * that is essential for both openbadge and edci certificats
 * and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class CredentialSubject
{
    /**
     * @var string Unique identifier for use in edci.
     */
    private $identifier = "";

    /**
     * @var string Email.
     */
    private $email = "";

    /**
     * @var string Given names.
     */
    private $given_names = "";

    /**
     * @var string Family name.
     */
    private $family_name = "";

    /**
     * @var string Achievements.
     */
    private $achievements = [];

    /**
     * Constructor.
     */
    private function __construct()
    {
    }

     /**
     * Creates a CredentialSubject Object based on an edci certificate.
     *
     * @param BCert $bcert BCert that references this object.
     * @param MySimpleXMLElement $xml Contains the credential subject information in edci format.
     * @return CredentialSubject
     */
    public static function from_edci($bcert, $xml)
    {
        $new = new CredentialSubject();
        $data = $xml->credentialSubject;
        $new->identifier = (string) $data->identifier;
        $new->email = str_replace('mailto:', '', $data->contactPoint->mailBox['uri']);
        $new->given_names = (string) $data->givenNames->text;
        $new->family_name = (string) $data->familyName->text;

        foreach ($data->achievements->learningAchievement as $learningAchievement) {
            array_push($new->achievements, Achievement::from_edci($bcert, $learningAchievement));
        }

        return $new;
    }

    /**
     * Creates a CredentialSubject Object based on an openBadge certificate.
     *
     * @param BCert $bcert BCert that references this object.
     * @param MySimpleXMLElement $json Contains the credential subject information in openBadge format.
     * @return CredentialSubject
     */
    public static function from_ob($bcert, $json)
    {
        $new = new CredentialSubject();
        $new->identifier = $json->{'extensions:recipientB4E'}->reference;
        $new->email = $json->{'extensions:recipientB4E'}->email;
        $new->given_names = $json->{'extensions:recipientB4E'}->givenname;
        $new->family_name = $json->{'extensions:recipientB4E'}->surname;

        $expertises = $json->badge->{'extensions:badgeexpertiseB4E'}->expertise;
        foreach ($expertises as $expertise) {
            array_push($new->achievements, Achievement::from_ob($bcert, $expertise));
        }
        return $new;
    }

    /**
     * Creates a default object that contains info about the expertises of a credential subject
     * by gathering data from its achievements for use in an openBadge cert.
     *
     * @return object
     */
    public function get_expertise()
    {
        // get expertises from achievements
        $expertise = [];
        foreach ($this->achievements as $achievement) {
            array_push($expertise, $achievement->get_expertise());
        }

        return (object) [
            '@context' => 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/BadgeExpertiseB4E/context.json',
            'type' => ["Extension", "BadgeExpertiseB4E"],
            'expertise' => $expertise
        ];
    }

    /**
     * Returns a default Object containing credential subject data in openBadge format.
     *
     * @return object
     */
    public function get_ob()
    {
        $recipient = new stdClass();
        $recipient->reference = $this->identifier;
        $recipient->email = $this->email;
        $recipient->{'@context'} = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/RecipientB4E/context.json';
        $recipient->type = ["Extension", "RecipientB4E"];
        $recipient->givenname = $this->given_names;
        $recipient->surname = $this->family_name;
        return $recipient;
    }

    /**
     * Returns a MySimpleXMLElement containing credential subject data in edci format.
     *
     * @return MySimpleXMLElement
     */
    public function get_edci()
    {
        $root = MySimpleXMLElement::create_empty('credentialSubject');
        $root->addAttribute('id', 'urn:bcert:person:1');
        $root->addChild('identifier', $this->identifier);
        $contact_point = $root->addChild('contactPoint');
        $mail_box = $contact_point->addChild('mailBox');
        $mail_box->addAttribute('uri', 'mailto:' . $this->email);
        $root->addTextNode('givenNames', $this->given_names);
        $root->addTextNode('familyName', $this->family_name);

        $achievementsNode = $root->addChild('achievements');
        foreach ($this->achievements as $achievement) {
            $achievementsNode->appendXML($achievement->get_edci());
        }

        return $root;
    }
}