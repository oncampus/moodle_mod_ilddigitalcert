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

require_once('mySimpleXMLElement.php');

/**
 * A verification object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Contract
{

    /**
     * @var string smart contract info.
     */
    private $abi = "";

    /**
     * @var string Blockchain address.
     */
    private $address = "";

    /**
     * @var string The node the certificate was initialy introduced to the blockchain.
     */
    private $node = "";

    /**
     * Constructor.
     */
    private function __construct()
    {
    }

     /**
     * Creates a Contract Object based on an edci certificate.
     *
     * @param MySimpleXMLElement $xml Contains the contract information in edci format.
     * @return Contract
     */
    public static function from_edci($xml)
    {
        $new = new Contract();
        $new->abi = (string) $xml->contract->abi;
        $new->address = (string) $xml->contract->address;
        $new->node = (string) $xml->contract->node;
        return $new;
    }

    /**
     * Creates a Contract Object based on an openBadge certificate.
     *
     * @param MySimpleXMLElement $json Contains the contract information in openBadge format.
     * @return Contract
     */
    public static function from_ob($json)
    {
        $new = new Contract();
        $new->abi = $json->{'extensions:contractB4E'}->abi;
        $new->address = $json->{'extensions:contractB4E'}->address;
        $new->node = $json->{'extensions:contractB4E'}->node;
        return $new;
    }


    /**
     * Returns a default Object containing contract data in openBadge format.
     *
     * @return object
     */
    public function get_ob()
    {
        $cobtract = new stdClass();
        $cobtract->{'@context'} = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/ContractB4E/context.json';
        $cobtract->type = ["Extension", "ContractB4E"];
        $cobtract->abi = $this->abi;
        $cobtract->address = $this->address;
        $cobtract->node = $this->node;
        return $cobtract;
    }


    /**
     * Returns a MySimpleXMLElement containing contract data in edci format.
     *
     * @return MySimpleXMLElement
     */
    public function get_edci()
    {
        $root = MySimpleXMLElement::create_empty('contract');

        $root->addChild('abi', xml_escape($this->abi));
        $root->addChild('address', xml_escape($this->address));
        $root->addChild('node', xml_escape($this->node));

        return $root;
    }
}