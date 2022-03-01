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
 * A signature object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class signature
{

    /**
     * @var string Blockchain address of the certifier.
     */
    private $address = "";

    /**
     * @var string Email of the certifier.
     */
    private $email = "";

    /**
     * @var string Given name of the cerifier.
     */
    private $givenname = "";

    /**
     * @var string Surname of the certifier.
     */
    private $surname = "";

    /**
     * @var string Role of the certifier in his organization.
     */
    private $role = "";

    /**
     * @var string Datetime of the moment the certificate was signed.
     */
    private $certificationdate = "";

    /**
     * Constructor.
     */
    private function __construct()
    {
    }

    /**
     * Creates a signature Object based on an edci certificate.
     *
     * @param mySimpleXMLElement $xml Contains the signature information in edci format.
     * @return signature
     */
    public static function from_edci($xml)
    {
        $new = new signature();
        $new->address = (string) $xml->signature->address;
        $new->email = (string)  $xml->signature->email;
        $new->givenname = (string) $xml->signature->givenname;
        $new->surname = (string) $xml->signature->surname;
        $new->role = (string) $xml->signature->role;
        $new->certificationdate = (string) $xml->signature->certificationdate;
        return $new;
    }

    /**
     * Creates a signature Object based on an openBadge certificate.
     *
     * @param mySimpleXMLElement $json Contains the signature information in openBadge format.
     * @return signature
     */
    public static function from_ob($json)
    {
        $new = new signature();
        $new->address = $json->{'extensions:signatureB4E'}->address;
        $new->email = $json->{'extensions:signatureB4E'}->email;
        $new->givenname = $json->{'extensions:signatureB4E'}->givenname;
        $new->surname = $json->{'extensions:signatureB4E'}->surname;
        $new->role = $json->{'extensions:signatureB4E'}->role;
        $new->certificationdate = $json->{'extensions:signatureB4E'}->certificationdate;
        return $new;
    }

    /**
     * Returns a default Object containing signature data in openBadge format.
     *
     * @return object
     */
    public function get_ob()
    {
        $signature = new \stdClass();
        $signature->address = $this->address;
        $signature->email = $this->email;
        $signature->surname = $this->surname;
        $signature->{'@context'} = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/SignatureB4E/context.json';
        $signature->role = $this->role;
        $signature->certificationdate = $this->certificationdate;
        $signature->type = ["Extension", "SignatureB4E"];
        $signature->givenname = $this->givenname;
        return $signature;
    }

    /**
     * Returns a mySimpleXMLElement containing signature data in edci format.
     *
     * @return mySimpleXMLElement
     */
    public function get_edci()
    {
        $root = mySimpleXMLElement::create_empty('signature');

        $root->addChild('address', $this->address);
        $root->addChild('email', $this->email);
        $root->addChild('givenname', $this->givenname);
        $root->addChild('surname', $this->surname);
        $root->addChild('role', $this->role);
        $root->addChild('certificationdate', $this->certificationdate);

        return $root;
    }
}
