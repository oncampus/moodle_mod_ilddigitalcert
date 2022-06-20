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

use mod_ilddigitalcert\web3_manager;
use moodle_exception;

/**
 * A verification object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class contract {
    /** @var string smart contract info. */
    private $abi;

    /** @var string Blockchain address. */
    private $address;

    /** @var string The node the certificate was initialy introduced to the blockchain. */
    private $node;

    /**
     * Constructor.
     */
    private function __construct() {
    }

    /**
     * Creates a contract object.
     *
     * @return contract
     */
    public static function new() {
        $new = new self();

        $contract = web3_manager::get_certificate_contract();
        $node = web3_manager::get_node();

        if (!isset($contract) || !isset($node)) {
            throw new moodle_exception('Could\'nt get contract and node from API');
        }

        $new->abi = json_encode($contract->contract_abi);
        $new->address = $contract->contract_address;
        $new->node = $node->url;

        return $new;
    }

    /**
     * Creates a contract Object based on an edci certificate.
     *
     * @param mySimpleXMLElement $xml Contains the contract information in edci format.
     * @return contract
     */
    public static function from_edci($xml) {
        if (!isset($xml->contract)) {
            return null;
        }
        $new = new contract();
        $new->abi = (string) $xml->contract->abi;
        $new->address = (string) $xml->contract->address;
        $new->node = (string) $xml->contract->node;
        return $new;
    }

    /**
     * Creates a contract Object based on an openBadge certificate.
     *
     * @param \stdClass $json Contains the contract information in openBadge format.
     * @return contract
     */
    public static function from_ob($json) {
        if (!isset($json->{'extensions:contractB4E'})) {
            return null;
        }
        $new = new contract();
        $new->abi = $json->{'extensions:contractB4E'}->abi;
        $new->address = $json->{'extensions:contractB4E'}->address;
        $new->node = $json->{'extensions:contractB4E'}->node;
        return $new;
    }


    /**
     * Returns a default Object containing contract data in openBadge format.
     *
     * @return \stdClass
     */
    public function get_ob() {
        $contract = new \stdClass();
        $contract->{'@context'} = \mod_ilddigitalcert\bcert\manager::CONTEXT_B4E_CONTRACT;
        $contract->type = ["Extension", "ContractB4E"];
        $contract->abi = $this->abi;
        $contract->address = $this->address;
        $contract->node = $this->node;
        return $contract;
    }


    /**
     * Returns a mySimpleXMLElement containing contract data in edci format.
     *
     * @return mySimpleXMLElement
     */
    public function get_edci() {
        $root = mySimpleXMLElement::create_empty('contract');

        $root->addChild('abi', manager::xml_escape($this->abi));
        $root->addChild('address', manager::xml_escape($this->address));
        $root->addChild('node', manager::xml_escape($this->node));

        return $root;
    }
}
