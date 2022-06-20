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

/**
 * Library of functions to interact with blockchain.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ilddigitalcert;

defined('MOODLE_INTERNAL') || die();

require('vendor/autoload.php');
require_once($CFG->dirroot . '/lib/filelib.php');

use Web3\Web3;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Contract;
use Web3p\EthereumTx\Transaction;
use Web3p\EthereumUtil\Util;

/**
 * Library of functions to interact with blockchain using the DigitalCertAPI.
 *
 * @package    mod_ilddigitalcert
 * @copyright  2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class web3_manager {


    /**
     * Get the base URL of the digital certificate API.
     *
     * When demo mode is activated in the plugin settings,the function returns
     * the url for the corresponding API that is connected to the demo blockchain.
     *
     * @return string Base URL of API.
     */
    private static function get_base_url() {
        if (get_config('mod_ilddigitalcert', 'demo_mode')) {
            return 'https://dev-isy.th-luebeck.de/digitalcertapi';
        }
        throw new \moodle_exception('The production API is not yet available. Please enable demo_mode.');
        // TODO: Set production API URL.
        return 'https://dev-isy.th-luebeck.de/digitalcertapi';
    }

    /**
     * Checks if the blockchain node identified by the given $url is active.
     *
     * @param string $url
     * @return boolean True if the node is active, else false;
     */
    private static function check_node($url) {
        if (PHPUNIT_TEST) {
            return true;
        }
        $web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
        $success = false;
        $web3->eth->blockNumber(function ($err, $blocknumber) use (&$success) {
            if ($err === null) {
                $block = $blocknumber->value;
                if ($block > 0) {
                    $success = true;
                }
            }
        });
        // TODO if !$success throw error.
        return $success;
    }

    /**
     * Write the given certificate $hash in the blockchain with a given privatekey.
     *
     * @param string $hash
     * @param int $startdate
     * @param int $enddate
     * @param string $pk Private key.
     * @return stdClass Object containing transaction and certificate hashes.
     */
    public static function store_certificate($hash, $startdate, $enddate, $pk) {
        // Use the API for demo purposes.
        if (get_config('mod_ilddigitalcert', 'demo_mode')) {
            $curl = new \curl();
            $curl->setHeader('Content-Type: application/json');
            $params = [
                'hash' => $hash,
                'pk' => $pk,
                'startdate' => $startdate,
                'enddate' => $enddate,
            ];
            $response = json_decode($curl->post(self::get_base_url()  . '/certificate', json_encode($params)));
            $httpcode = $curl->get_info()['http_code'];
            if ($httpcode >= 200 && $httpcode < 300) {
                return $response;
            }

            return null;
        }

        $url = self::get_node()->url;
        $account = self::get_address_from_pk($pk);

        $contractschema = self::get_certificate_contract();
        $contractabi = json_encode($contractschema->contract_abi);
        $contractaddress = $contractschema->contract_address;

        $storehash = $hash;
        $chainid = 10; // TODO: into settings.

        $web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
        $eth = $web3->eth;

        $contract = new Contract($web3->provider, $contractabi);

        $contract->at($contractaddress);

        $hashes = new \stdClass();
        $hashes->certhash = $hash;

        $nonce = 0;
        $r = $eth->getTransactionCount($account, 'latest', function ($err, $data)  use (&$nonce) {
            if ($err !== null) {
                throw $err;
            }
            $nonce = $data->toString();
        });

        $functiondata = $contract->getData('storeCertificate', $storehash, $startdate, $enddate);

        $transaction = new Transaction(array(
            'from' => $account,
            'nonce' => '0x' . dechex($nonce),
            'to' => $contractaddress,
            'gas' => dechex(450),
            'data' => '0x' . $functiondata,
            'chainId' => $chainid
        ));
        $signedtransaction = $transaction->sign($pk);

        $eth->sendRawTransaction('0x' . $signedtransaction, function ($err, $tx) use (&$hashes) {
            if ($err !== null) {
                throw $err;
            }
            $hashes->txhash = $tx;
        });

        // TODO warum geht das nicht (getTransactionReceipt)?

        // Prüfen ob Zertifikat auch in BC exisiert.
        $start = time();
        do {
            $now = time();
            $cert = self::get_certificate($hash);
            if (isset($cert['valid']) && $cert['valid'] == true) {
                return $hashes;
            }
        } while ($now - $start < 30);

        return null;
    }

    /**
     * Revocation of a certificate identified by $certhash using the $pk of a certifier.
     *
     * @param string $hash Hash of certificate, that will be revoked.
     * @param string $pk Private Key of certifier
     *
     * @return bool True if revocation was successful, else false
     */
    public static function revoke_certificate($hash, $pk) {
        // Use the API for demo purposes.
        if (get_config('mod_ilddigitalcert', 'demo_mode')) {
            $certificate = self::get_certificate($hash);
            if (!isset($certificate) || $certificate->valid == false) {
                return true;
            }

            $curl = new \curl();
            $curl->setHeader('Content-Type: application/json');
            $params = [
                'pk' => $pk,
            ];
            $curl->delete(self::get_base_url()  . '/certificate/' . $hash, null, ['CURLOPT_POSTFIELDS' => json_encode($params)]);
            $httpcode = $curl->get_info()['http_code'];

            return ($httpcode >= 200 && $httpcode < 300);
        }

        $url = self::get_node()->url;
        $account = self::get_address_from_pk($pk);

        $contractschema = self::get_certificate_contract();
        $contractabi = json_encode($contractschema->contract_abi);
        $contractaddress = $contractschema->contract_address;

        $chainid = 10; // TODO: into settings.

        $web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
        $eth = $web3->eth;

        $contract = new Contract($web3->provider, $contractabi);
        $contract->at($contractaddress);

        $nonce = 0;
        $r = $eth->getTransactionCount($account, 'latest', function ($err, $data)  use (&$nonce) {
            if ($err !== null) {
                throw $err;
            }
            $nonce = $data->toString();
        });

        $functiondata = $contract->getData('revokeCertificate', $hash);

        $transaction = new Transaction(array(
            'from' => $account,
            'nonce' => '0x' . dechex($nonce),
            'to' => $contractaddress,
            'gas' => dechex(450),
            'data' => '0x' . $functiondata,
            'chainId' => $chainid
        ));
        $signedtransaction = $transaction->sign($pk);

        $eth->sendRawTransaction('0x' . $signedtransaction, function ($err, $tx) {
            if ($err !== null) {
                throw $err;
            }
        });

        $start = time();

        do {
            $now = time();
            $cert = self::get_certificate($hash);
            if (!is_array($cert) || !array_key_exists('valid', $cert) || $cert['valid'] == false) {
                return true;
            }
        } while ($now - $start < 30);

        return false;
    }

    /**
     * Retrieves the certificate information stored in the blockchain that is identified by the given hash.
     *
     * @param string $hash
     * @return stdClass
     */
    public static function get_certificate($hash) {
        // Use the API for demo purposes.
        if (get_config('mod_ilddigitalcert', 'demo_mode')) {
            $curl = new \curl();
            $response = json_decode($curl->get(self::get_base_url()  . '/certificate/' . $hash));
            $httpcode = $curl->get_info()['http_code'];
            if ($httpcode >= 200 && $httpcode < 300) {
                return $response;
            }

            return null;
        }

        $cert = null;

        $web3 = new Web3(new HttpProvider(new HttpRequestManager(self::get_node()->url, 30)));

        $contractschema = self::get_certificate_contract();
        $contractabi = json_encode($contractschema->contract_abi);
        $contractaddress = $contractschema->contract_address;

        $contract = new Contract($web3->provider, $contractabi);
        $contract->at($contractaddress);

        try {
            $contract->call('getCertificate', $hash, function ($err, $result) use (&$cert) {
                if ($err !== null) {
                    throw $err;
                }
                if ($result) {
                    $cert = [
                        'institution' => $result[2],
                        'institutionProfile' => $result[3],
                        'startingDate' => $result[4][0]->value,
                        'endingDate' => $result[4][1]->value,
                        'onHold' => $result[5]->value,
                        'valid' => $result[6],
                    ];
                }
            });
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        return $cert;
    }

    /**
     * Retrieves the certificate smart contract used for transactions.
     *
     * @param string $hash
     * @return stdClass
     */
    public static function get_certificate_contract() {
        // Use the API for demo purposes.
        if (get_config('mod_ilddigitalcert', 'demo_mode')) {
            $curl = new \curl();
            $response = json_decode($curl->get(self::get_base_url()  . '/certificate/contract'));
            $httpcode = $curl->get_info()['http_code'];
            if ($httpcode >= 200 && $httpcode < 300) {
                return $response;
            }

            return null;
        }

        global $CFG;

        $filename = $CFG->dirroot . '/mod/ilddigitalcert/contracts/CertificateManagement_prod.json';
        return json_decode(file_get_contents($filename));
    }

    /**
     * Retrieves the identity smart contract used for transactions.
     *
     * @param string $hash
     * @return stdClass
     */
    public static function get_identity_contract() {
        global $CFG;

        $filename = $CFG->dirroot . '/mod/ilddigitalcert/contracts/IdentityManagement_prod.json';
        return json_decode(file_get_contents($filename));
    }

    /**
     * Retrieves the blockchain node the api communicates with.
     *
     * @param string $hash
     * @return stdClass
     */
    public static function get_node() {
        // Use the API for demo purposes.
        if (get_config('mod_ilddigitalcert', 'demo_mode')) {
            $curl = new \curl();
            $response = json_decode($curl->get(self::get_base_url()  . '/node'));
            $httpcode = $curl->get_info()['http_code'];
            if ($httpcode >= 200 && $httpcode < 300) {
                return $response;
            }

            return null;
        }

        $blockchainurl = get_config('ilddigitalcert', 'blockchain_url');
        $failoverurl = get_config('ilddigitalcert', 'failover_url');
        // TODO mehrere failover-addressen mit komma getrennt möglich machen.
        // Check if node is working.
        if (isset($blockchainurl) and $blockchainurl != '' and self::check_node($blockchainurl)) {
            return (object) ["url" => $blockchainurl];
        } else if (isset($failoverurl) and $failoverurl != '' and self::check_node($failoverurl)) {
            return (object) ["url" => $failoverurl];
        }

        throw new \moodle_exception(
            get_string('error_novalidblockchainurl', 'mod_ilddigitalcert'),
            '',
            new \moodle_url('/admin/settings.php', array('section' => 'modsettingilddigitalcert'))
        );
    }

    /**
     * Registers a new certifier on the blockchain for a user with $useraddress and using the private key of an admin.
     *
     * @param string $useraddress
     * @param string $pk
     * @return boolean True if certifier was added successfully, else false.
     */
    public static function add_certifier_to_blockchain($useraddress, $pk) {
        // Use the API for demo purposes.
        if (get_config('mod_ilddigitalcert', 'demo_mode')) {
            $curl = new \curl();
            $curl->setHeader('Content-Type: application/json');
            $params = [
                'address' => $useraddress,
                'pk' => $pk,
            ];
            $response = json_decode($curl->post(self::get_base_url()  . '/certifier', json_encode($params)));
            $httpcode = $curl->get_info()['http_code'];

            return ($httpcode >= 200 && $httpcode < 300);
        }

        $url = self::get_node()->url;
        $account = self::get_address_from_pk($pk);

        $contractschema = self::get_identity_contract();
        $contractabi = json_encode($contractschema->contract_abi);
        $contractaddress = $contractschema->contract_address;

        $chainid = 10; // TODO: into settings.

        $web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
        $eth = $web3->eth;

        $contract = new Contract($web3->provider, $contractabi);
        $contract->at($contractaddress);

        $nonce = 0;
        $r = $eth->getTransactionCount($account, 'latest', function ($err, $data)  use (&$nonce) {
            if ($err !== null) {
                throw $err;
            }
            $nonce = $data->toString();
        });

        $functiondata = $contract->getData('registerCertifier', $useraddress);

        $transaction = new Transaction(array(
            'from' => $account,
            'nonce' => '0x' . dechex($nonce),
            'to' => $contractaddress,
            'gas' => dechex(450),
            'data' => '0x' . $functiondata,
            'chainId' => $chainid
        ));

        $signedtransaction = $transaction->sign($pk);

        $eth->sendRawTransaction('0x' . $signedtransaction, function ($err, $tx) {
            if ($err !== null) {
                throw $err;
            }
        });

        return true;
    }

    /**
     * Checks if the given $address belongs to a registered certifier.
     *
     * @param string $address
     * @return boolean True if $address blongs to a registered certifier, else false.
     */
    public static function is_accredited_certifier($address) {
        // Use the API for demo purposes.
        if (get_config('mod_ilddigitalcert', 'demo_mode')) {
            $curl = new \curl();
            $response = json_decode($curl->get(self::get_base_url()  . '/certifier/' . $address));

            if (isset($response->is_accredited)) {
                return $response->is_accredited;
            }

            return false;
        }

        $isaccreditedcertifier = false;

        $web3 = new Web3(new HttpProvider(new HttpRequestManager(self::get_node()->url, 30)));

        $contractschema = self::get_identity_contract();
        $contractabi = json_encode($contractschema->contract_abi);
        $contractaddress = $contractschema->contract_address;

        $contract = new Contract($web3->provider, $contractabi);
        $contract->at($contractaddress);
        $contract->call('isAccreditedCertifier', $address, function ($err, $result) use (&$isaccreditedcertifier) {
            if ($err !== null) {
                throw $err;
            }
            if ($result) {
                $isaccreditedcertifier = $result[0];
            }
        });

        return $isaccreditedcertifier;
    }

    /**
     * Unregisteres a certifier identified by $useraddress on the bockchain with the given privatekey $adminpk.
     *
     * @param string $useraddress
     * @param string $pk
     * @return bool True if certifier was successfully removed from the blockchain, else false.
     */
    public static function remove_certifier_from_blockchain($useraddress, $pk) {
        // Use the API for demo purposes.
        if (get_config('mod_ilddigitalcert', 'demo_mode')) {
            $curl = new \curl();
            $curl->setHeader('Content-Type: application/json');
            $params = [
                'pk' => $pk,
            ];
            $curl->delete(self::get_base_url()  . '/certifier/' . $useraddress, null, ['CURLOPT_POSTFIELDS' => json_encode($params)]);
            $httpcode = $curl->get_info()['http_code'];

            return ($httpcode >= 200 && $httpcode < 300);
        }

        $url = self::get_node()->url;
        $account = self::get_address_from_pk($pk);

        $contractschema = self::get_identity_contract();
        $contractabi = json_encode($contractschema->contract_abi);
        $contractaddress = $contractschema->contract_address;

        $chainid = 10; // TODO: into settings.

        $web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
        $eth = $web3->eth;

        $contract = new Contract($web3->provider, $contractabi);
        $contract->at($contractaddress);

        $nonce = 0;
        $r = $eth->getTransactionCount($account, 'latest', function ($err, $data)  use (&$nonce) {
            if ($err !== null) {
                throw $err;
            }
            $nonce = $data->toString();
        });
        $functiondata = $contract->getData('removeCertifier', $useraddress);

        $transaction = new Transaction(array(
            'from' => $account,
            'nonce' => '0x' . dechex($nonce),
            'to' => $contractaddress,
            'gas' => dechex(450),
            'data' => '0x' . $functiondata,
            'chainId' => $chainid
        ));
        $signedtransaction = $transaction->sign($pk);

        $eth->sendRawTransaction('0x' . $signedtransaction, function ($err, $tx) {
            if ($err !== null) {
                throw $err;
            }
        });

        return true;
    }

    /**
     * Get the blockchain address of an institution the certifier, that is identified by $address, belongs to.
     *
     * @param string $address
     * @return string
     */
    public static function get_institution_from_certifier($address) {

        // Use the API for demo purposes.
        if (get_config('mod_ilddigitalcert', 'demo_mode')) {
            $curl = new \curl();
            $response = json_decode($curl->get(self::get_base_url()  . '/certifier/' . $address . '/institution'));
            $httpcode = $curl->get_info()['http_code'];
            if ($httpcode >= 200 && $httpcode < 300) {
                return $response->address;
            }

            return null;
        }

        $institutionaddress = null;

        $web3 = new Web3(new HttpProvider(new HttpRequestManager(self::get_node()->url, 30)));

        $contractschema = self::get_identity_contract();
        $contractabi = json_encode($contractschema->contract_abi);
        $contractaddress = $contractschema->contract_address;

        $contract = new Contract($web3->provider, $contractabi);
        $contract->at($contractaddress);
        $contract->call('getInstitutionFromCertifier', $address, function ($err, $result) use (&$institutionaddress) {
            if ($err !== null) {
                throw $err;
            }
            if ($result) {
                $institutionaddress = $result[0];
            }
        });

        return $institutionaddress;
    }

    /**
     * Get the blockchain address belonging to a given privatekey $pk.
     *
     * @param string $pk
     * @return string
     */
    public static function get_address_from_pk($pk) {
        $util = new Util();
        $publickey = $util->privateKeyToPublicKey($pk);
        $address = $util->publicKeyToAddress($publickey);
        return $address;
    }
}
