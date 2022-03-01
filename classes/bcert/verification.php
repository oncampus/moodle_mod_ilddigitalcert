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
 * A verification object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class verification
{
    /**
     * @var string Url that a user can use to verify the validity of a certificate.
     */
    private $verifyaddress = "";

    /**
     * @var string is a hash of the json certificate.
     */
    private $assertionhash = "";

    /**
     * Constructor.
     */
    private function __construct()
    {
    }

    /**
     * Creates a verification Object based on an edci certificate.
     *
     * @param mySimpleXMLElement $xml Contains the certificate information in edci format.
     * @return verification
     */
    public static function from_edci($xml)
    {
        $new = new verification();
        $new->verifyaddress = (string) $xml->verification->verifyaddress;
        $new->assertionhash = (string) $xml->verification->assertionhash;
        return $new;
    }

    /**
     * Creates a verification Object based on an openBadge certificate.
     *
     * @param mySimpleXMLElement $json Contains the certificate information in openBadge format.
     * @return verification
     */
    public static function from_ob($json)
    {
        $new = new verification();
        $new->verifyaddress = $json->verification->{'extensions:verifyB4E'}->verifyaddress;
        $new->assertionhash = $json->verification->{'extensions:verifyB4E'}->assertionhash;
        return $new;
    }

    /**
     * Returns a default Object containing verification data in openBadge format.
     *
     * @return object
     */
    public function get_ob()
    {
        $verification = new \stdClass();
        $verification->{'extensions:verifyB4E'} = new \stdClass();
        $verification->{'extensions:verifyB4E'}->verifyaddress = $this->verifyaddress;
        $verification->{'extensions:verifyB4E'}->type = ["Extension", "VerifyB4E"];
        $verification->{'extensions:verifyB4E'}->assertionhash = $this->assertionhash;
        $verification->{'extensions:verifyB4E'}->{'@context'} = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/VerifyB4E/context.json';
        return $verification;
    }

    /**
     * Returns a mySimpleXMLElement containing verification data in edci format.
     *
     * @return mySimpleXMLElement
     */
    public function get_edci()
    {
        $root = mySimpleXMLElement::create_empty('verification');

        $root->addChild('verifyaddress', manager::xml_escape($this->verifyaddress));
        $root->addChild('assertionhash', manager::xml_escape($this->assertionhash));

        return $root;
    }
}
