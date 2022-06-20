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
 * A organization object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class organization
{

    /** @var  int counter that gets incremented with every organization object that gets created, used to generate a unique id. */
    private static $count = 0;

    /** @var string Unique identifier. */
    private $id;

    /** @var string EDCI identifier. */
    private $identifier;

    /** @var string Edci registration title. */
    private $registration = "DUMMY-REGISTRATION";

    /** @var string Prefered title. */
    private $preflabel;

    /** @var string Alternative title or description. Optional attribute. */
    private $altlabel;

    /** @var string Homepage. */
    private $homepage;

    /** @var string Country the organization is registered at. */
    private $location;

    /** @var string ZIP Code. */
    private $zip;

    /** @var string Street name and number. */
    private $street;

    /** @var string Post office box number. Optional attribute. */
    private $pob;

    /** @var string Address including location, zip and street. */
    private $fulladdress;

    /** @var image Logo. Optional attribute. */
    private $logo;

    /** @var string Email. */
    private $email;

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
        self::$count += 1;
    }

    /**
     * Creates an organization object based on
     *
     * @param \stdClass $issuer
     * @return void
     */
    public static function issuer($issuer)
    {
        $new = new self();

        $new->id = 'urn:bcert:org:' . self::$count;
        $new->identifier = (new \moodle_url(
            '/mod/ilddigitalcert/edit_issuers.php',
            array('action' => 'edit', 'id' => $issuer->id)
        ))->out();
        $new->preflabel = $issuer->name;
        if (isset($issuer->description)) {
            $new->altlabel = $issuer->description;
        }
        $new->email = $issuer->email;
        $new->homepage = $issuer->url;
        $new->location = $issuer->location;
        $new->zip = $issuer->zip;
        $new->street = $issuer->street;
        $new->fulladdress = $new->street;
        if (isset($issuer->pob)) {
            $new->pob = $issuer->pob;
            $new->fulladdress .= ', PO Box ' . $new->pob;
        }
        $new->fulladdress .= ', ' . $new->zip . ' ' . $new->location;
        $new->logo = image::new(\mod_ilddigitalcert\bcert\manager::get_issuer_image($issuer->id), 'logo');

        return $new;
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
        $orgxml = $xml->agentReferences->organization[0];
        $org->id = $orgxml['id'];
        $org->identifier = (string) $orgxml->identifier;
        $org->preflabel = (string) $orgxml->prefLabel->text;
        if (isset($orgxml->altLabel)) {
            $org->altlabel = (string) $orgxml->altLabel->text;
        }
        $org->email = str_replace('mailto:', '', $orgxml->contactPoint->mailBox['uri']);
        $org->homepage = (string) $orgxml->homepage['uri'];
        $org->location = (string) $orgxml->hasLocation->hasAddress->location->text;
        $org->zip = (string) $orgxml->hasLocation->hasAddress->zip;
        $org->street = (string) $orgxml->hasLocation->hasAddress->street->text;
        $org->fulladdress = $orgxml->hasLocation->hasAddress->fullAddress->text;
        if (isset($orgxml->hasLocation->hasAddress->pob)) {
            $org->pob = (string) $orgxml->hasLocation->hasAddress->pob;
        }
        if (isset($orgxml->logo)) {
            $org->logo = image::from_edci($orgxml->logo);
        }
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
        $org->id = 'urn:bcert:org:' . self::$count;
        $org->identifier = $data->badge->issuer->id;
        $org->preflabel = $data->badge->issuer->name;
        $org->altlabel = manager::get_if_key_exists($data->badge->issuer, 'description');
        $org->email = $data->badge->issuer->email;
        $org->homepage = $data->badge->issuer->url;
        $org->location = $data->badge->issuer->{'extensions:addressB4E'}->location;
        $org->zip = $data->badge->issuer->{'extensions:addressB4E'}->zip;
        $org->street = $data->badge->issuer->{'extensions:addressB4E'}->street;
        $org->pob = manager::get_if_key_exists($data->badge->issuer->{'extensions:addressB4E'}, 'pob');
        $org->fulladdress = $org->street;
        if (isset($org->pob)) {
            $org->fulladdress .= ', PO Box ' . $org->pob;
        }
        $org->fulladdress .= ', ' . $org->zip . ' ' . $org->location;
        if (isset($data->badge->issuer->image)) {
            $org->logo = image::from_ob('logo', $data->badge->issuer->image);
        }
        return $org;
    }

    /**
     * Returns a default Object containing organization data in openBadge format.
     *
     * @return \stdClass
     */
    public function get_ob()
    {
        $issuer = new \stdClass();
        if (isset($this->altlabel)) {
            $issuer->description = $this->altlabel;
        }
        $issuer->{'extensions:addressB4E'} = (object) [
            'location' => $this->location,
            'zip' => $this->zip,
            'street' => $this->street,
            '@context' => \mod_ilddigitalcert\bcert\manager::CONTEXT_B4E_ADDRESS,
            'type' => ["Extension", "AddressB4E"]
        ];
        if (isset($this->pob)) {
            $issuer->{'extensions:addressB4E'}->pob = $this->pob;
        }
        $issuer->email = $this->email;
        $issuer->name = $this->preflabel;
        $issuer->url = $this->homepage;
        $issuer->{'@context'} = \mod_ilddigitalcert\bcert\manager::CONTEXT_OPENBADGES;
        $issuer->type = 'Issuer';
        $issuer->id = $this->identifier;
        if (isset($this->logo)) {
            $issuer->image = $this->logo->get_ob();
        }

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
        $contactpoint = $root->addChild('contactPoint');
        $mailbox = $contactpoint->addChild('mailBox');
        $mailbox->addAttribute('uri', 'mailto:' . $this->email);
        $root->addChild('registration', $this->registration);
        $root->addtextnode('prefLabel', $this->preflabel);
        if (isset($this->altlabel)) {
            $root->addtextnode('altLabel', $this->altlabel);
        }
        $root->addChild('homepage')->addAttribute('uri', $this->homepage);
        $haslocation = $root->addChild('hasLocation');
        $hasaddress = $haslocation->addChild('hasAddress');
        $hasaddress->addtextnode('fullAddress', $this->fulladdress);
        $hasaddress->addtextnode('location', $this->location);
        $hasaddress->addChild('zip', $this->zip);
        $hasaddress->addtextnode('street', $this->street);
        if (isset($this->pob)) {
            $hasaddress->addChild('pob', $this->pob);
        }

        if (isset($this->logo)) {
            $root->appendXML($this->logo->get_edci());
        }

        return $root;
    }
}
