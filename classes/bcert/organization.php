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

defined('MOODLE_INTERNAL') || die();

/**
 * A organization object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class organization
{
    /**
     * @var  int counter that gets incremented with every organization object that gets created, used to generate a unique id.
     */
    private static $count = 0;

    /**
     * @var string Unique identifier.
     */
    private $id = "";

    /**
     * @var string EDCI identifier.
     */
    private $identifier = "";

    /**
     * @var string Edci registration title.
     */
    private $registration = "DUMMY-REGISTRATION";

    /**
     * @var string Prefered title.
     */
    private $pref_label = "";

    /**
     * @var string Alternative title or description.
     */
    private $alt_label = "";

    /**
     * @var string Homepage.
     */
    private $homepage = "";

    /**
     * @var string Country the organization is registered at.
     */
    private $location = "";

    /**
     * @var string ZIP Code.
     */
    private $zip = "";

    /**
     * @var string Street name and number.
     */
    private $street = "";

    /**
     * @var string Adress including location, zip and street.
     */
    private $full_address = "";

    /**
     * @var  image Logo.
     */
    private $logo;

    /**
     * @var string Email.
     */
    private $email = "";

    /**
     * @var string used as an unique identifier for the qualification.
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
    }

    /**
     * Creates a organization Object based on an edci certificate.
     *
     * @param mySimpleXMLElement $xml Contains the organization information in edci format.
     * @return organization
     */
    public static function from_edci($xml)
    {
        $org = new organization();
        $org_xml = $xml->agentReferences->organization[0];
        $org->id = $org_xml['id'];
        $org->identifier = (string) $org_xml->identifier;
        $org->pref_label = (string) $org_xml->prefLabel->text;
        $org->alt_label = (string) $org_xml->altLabel->text;
        $org->email = (string) $org_xml->email;
        $org->homepage = (string) $org_xml->homepage['uri'];
        $org->location = (string) $org_xml->hasLocation->hasAddress->location;
        $org->zip = (string) $org_xml->hasLocation->hasAddress->zip;
        $org->street = (string) $org_xml->hasLocation->hasAddress->street;
        $org->full_address = $org_xml->hasLocation->hasAddress->fullAddress->text;
        $org->logo = image::from_edci($org_xml->logo);
        return $org;
    }

    /**
     * Creates a organization Object based on an openBadge certificate.
     *
     * @param mySimpleXMLElement $json Contains the organization information in openBadge format.
     * @return organization
     */
    public static function from_ob($data)
    {
        $org = new organization();
        self::$count += 1;
        $org->id = 'urn:bcert:org:' . self::$count;
        $org->identifier = $data->badge->issuer->id;
        $org->pref_label = $data->badge->issuer->name;
        $org->alt_label = $data->badge->issuer->description;
        $org->email = $data->badge->issuer->email;
        $org->homepage = $data->badge->issuer->url;
        $org->location = $data->badge->issuer->{'extensions:addressB4E'}->location;
        $org->zip = $data->badge->issuer->{'extensions:addressB4E'}->zip;
        $org->street = $data->badge->issuer->{'extensions:addressB4E'}->street;
        $org->full_address = $org->street . ', ' . $org->zip . ' ' . $org->location;
        $org->logo = image::from_ob('logo', $data->badge->issuer->image);
        return $org;
    }

    /**
     * Returns a default Object containing organization data in openBadge format.
     *
     * @return object
     */
    public function get_ob()
    {
        $issuer = new \stdClass();
        $issuer->description = $this->alt_label;
        $issuer->{'extensions:addressB4E'} = (object) [
            'location' => $this->location,
            'zip' => $this->zip,
            'street' => $this->street,
            '@context' => 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/AddressB4E/context.json',
            'type' => ["Extension", "AddressB4E"]
        ];
        $issuer->email = $this->email;
        $issuer->name = $this->pref_label;
        $issuer->url = $this->homepage;
        $issuer->{'@context'} = 'https://w3id.org/openbadges/v2';
        $issuer->type = 'Issuer';
        $issuer->id = $this->identifier;
        $issuer->image = $this->logo->get_ob();

        return $issuer;
    }

    /**
     * Returns a mySimpleXMLElement containing organization data in edci format.
     *
     * @return mySimpleXMLElement
     */
    public function get_edci()
    {
        $root = mySimpleXMLElement::create_empty('organization');
        $root->addAttribute('id', $this->id);
        $root->addChild('identifier', manager::xml_escape($this->identifier));
        $root->addChild('registration', $this->registration);
        $root->addTextNode('prefLabel', $this->pref_label);
        $root->addTextNode('altLabel', $this->alt_label);
        $root->addChild('homepage')->addAttribute('uri', $this->homepage);
        $has_location = $root->addChild('hasLocation');
        $has_address = $has_location->addChild('hasAddress');
        $has_address->addTextNode('fullAddress', $this->full_address);
        $has_address->addChild('location', $this->location);
        $has_address->addChild('zip', $this->zip);
        $has_address->addChild('street', $this->street);

        $root->appendXML($this->logo->get_edci());

        $root->addChild('email', $this->email);

        return $root;
    }
}
