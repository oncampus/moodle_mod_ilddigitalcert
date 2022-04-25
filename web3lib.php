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

defined('MOODLE_INTERNAL') || die();

// if (!file_exists('vendor/autoload.php')) {
// require_once(__DIR__.'/../../config.php');

// require_login();
// $context = context_system::instance();

// $PAGE->set_context($context);
// $PAGE->set_url('/mod/ilddigitalcert/web3lib.php');
// $PAGE->set_title('web3lib.php error');
// $PAGE->set_heading('web3lib.php error');

// echo $OUTPUT->header();
// \core\notification::error(get_string('not_installed_correctly', 'mod_ilddigitalcert'));
// echo $OUTPUT->footer();
// die();
// }

require('vendor/autoload.php');

use Web3\Web3;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Contract;
use Web3p\EthereumTx\Transaction;
use Web3p\EthereumUtil\Util;

global $account;

/**
 * Resolves the name of an existing contract, based on a given simplified contractname.
 *
 * @param string $contractname
 * @return string
 */
function get_contract_name($contractname) {
    $contractnames = array('CertMgmt'       => 'CertificateManagement',
                        'IdentityMgmt' => 'IdentityManagement');
    if (!array_key_exists($contractname, $contractnames)) {
        throw new \coding_exception("Array key $contractname does not exist in given array.");
    }
    $contractname = $contractnames[$contractname];

    // Return the name of contracts, meant for production use, if the demo_mode is not activated.
    if (!get_config('ilddigitalcert', 'demo_mode')) {
        $contractname .= '_prod';
    }
    return $contractname;
}

/**
 * Checks if the blockchain node identified by the given $url is active.
 *
 * @param string $url
 * @return boolean True if the node is active, else false;
 */
function check_node($url) {
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
 * Get the logic of a smart contract identified by the given $contractname.
 *
 * @param string $contractname
 * @return string
 */
function get_contract_abi($contractname) {
    global $CFG;
    $contractname = get_contract_name($contractname);
    $filename = $CFG->dirroot.'/mod/ilddigitalcert/contracts/'.$contractname.'.json';
    $contract = json_decode(file_get_contents($filename));
    return json_encode($contract->contract_abi);
}

/**
 * Get the blockchain address of a smart contract identified by the given $contractname.
 *
 * @param string $contractname
 * @return string
 */
function get_contract_address($contractname) {
    global $CFG;
    $contractname = get_contract_name($contractname);
    $filename = $CFG->dirroot.'/mod/ilddigitalcert/contracts/'.$contractname.'.json';
    $contract = json_decode(file_get_contents($filename));

    return $contract->contract_address;
}

/**
 * Getter for the blockchain_url that is set in the plugin config. Checks wether the url identifies an active node on the network.
 * If it is not the failover_url is checked and returned if active. If both are invalid an error is thrown.
 *
 * @param string $contractname
 * @return string
 * @throws moodle_exception
 */
function get_contract_url($contractname) {
    $contractname = get_contract_name($contractname);

    $blockchainurl = get_config('ilddigitalcert', 'blockchain_url');
    $failoverurl = get_config('ilddigitalcert', 'failover_url');
    // TODO mehrere failover-adressen mit komma getrennt möglich machen.
    // Check if node is working.
    if (isset($blockchainurl) and $blockchainurl != '' and check_node($blockchainurl)) {
        return $blockchainurl;
    } else if (isset($failoverurl) and $failoverurl != '' and check_node($failoverurl)) {
        return $failoverurl;
    }
    throw new moodle_exception(
        get_string('error_novalidblockchainurl', 'mod_ilddigitalcert'),
        '',
        new moodle_url('/admin/settings.php', array('section' => 'modsettingilddigitalcert'))
    );
}

/**
 * Write the given certificate $hash in the blockchain with a given privatekey.
 *
 * @param string $hash
 * @param int $startdate
 * @param int $enddate
 * @param string $pk Provate key.
 * @return stdClass Object containing transaction and certificate hashes.
 */
function store_certificate($hash, $startdate, $enddate, $pk) {
    $url = get_contract_url('CertMgmt');
    $account = get_address_from_pk($pk);
    $contractabi = get_contract_abi('CertMgmt');
    $contractadress = get_contract_address('CertMgmt');
    $storehash = $hash;
    $chainid = 10; // TODO: into settings.

    $web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
    $eth = $web3->eth;

    $contract = new Contract($web3->provider, $contractabi);

    $contract->at($contractadress);

    $hashes = new stdClass();
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
            'nonce' => '0x'.dechex($nonce),
            'to' => $contractadress,
            'gas' => dechex(450),
            'data' => '0x'.$functiondata,
            'chainId' => $chainid
    ));
    $signedtransaction = $transaction->sign($pk);

    $eth->sendRawTransaction('0x'.$signedtransaction, function ($err, $tx) use (&$hashes){
        if ($err !== null) {
            throw $err;
        }
        $hashes->txhash = $tx;
    });

    // TODO warum geht das nicht (getTransactionReceipt)?

    // Prüfen ob Zertifikat auch in BC exisiert.
    $start = time();
    while (1) {
        $now = time();
        $cert = get_certificate($hashes->certhash);
        if (isset($cert->valid) and $cert->valid == 1) {
            return $hashes;
        }
        if ($now - $start > 30) {
            echo 'Error:';
            var_dump($hashes);
            unset($hashes->certhash);
            unset($hashes->txhash);
            break;
        }
    }
    return $hashes;
}

/**
 * Revocation of a certificate identified by $certhash using the $pk of a certifier.
 *
 * @param string $certhash Hash of certificate, that will be revoked.
 * @param string $pk Private Key of certifier
 *
 * @return bool True if revocation was successful, else false
 */
function revoke_certificate($certhash, $pk) {
    $url = get_contract_url('CertMgmt');
    $account = get_address_from_pk($pk);
    $contractabi = get_contract_abi('CertMgmt');
    $contractadress = get_contract_address('CertMgmt');
    $chainid = 10; // TODO: into settings.

    $web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
    $eth = $web3->eth;

    $contract = new Contract($web3->provider, $contractabi);
    $contract->at($contractadress);

    $nonce = 0;
    $r = $eth->getTransactionCount($account, 'latest', function ($err, $data)  use (&$nonce) {
        if ($err !== null) {
            throw $err;
        }
        $nonce = $data->toString();

    });

    $functiondata = $contract->getData('revokeCertificate', $certhash);

    $transaction = new Transaction(array(
            'from' => $account,
            'nonce' => '0x'.dechex($nonce),
            'to' => $contractadress,
            'gas' => dechex(450),
            'data' => '0x'.$functiondata,
            'chainId' => $chainid
    ));
    $signedtransaction = $transaction->sign($pk);

    $eth->sendRawTransaction('0x'.$signedtransaction, function ($err, $tx) {
        if ($err !== null) {
            throw $err;
        }
    });

    $start = time();
    while (1) {
        $now = time();
        $cert = get_certificate($certhash);
        if (isset($cert->valid) and $cert->valid != 1) {
            return true;
        }
        if ($now - $start > 30) {
            break;
        }
    }
    return false;
}

/**
 * Retrieves the certificate information stored in the blockchain that is identified by the given $certhash.
 *
 * @param string $certhash
 * @return stdClass
 */
function get_certificate($certhash) {
    $cert = new stdClass();

    $web3 = new Web3(new HttpProvider(new HttpRequestManager(get_contract_url('CertMgmt'), 30)));

    $contract = new Contract($web3->provider, get_contract_abi('CertMgmt'));
    $contract->at(get_contract_address('CertMgmt'));
    $contract->call('getCertificate', $certhash, function ($err, $result) use ($cert) {
        if ($err !== null) {
            throw $err;
        }
        if ($result) {
            $cert->institution = $result[2];
            $cert->institutionProfile = $result[3];
            $cert->startingDate = $result[4][0]->value;
            $cert->endingDate = $result[4][1]->value;
            $cert->onHold = $result[5]->value;
            $cert->valid = $result[6];
        }
    });
    return $cert;
}

/**
 * Registers a new certifier on the blockchain for a user with $useraddress and using the private key of an admin.
 *
 * @param string $useraddress
 * @param string $adminpk
 * @return void
 */
function add_certifier_to_blockchain($useraddress, $adminpk) {
    $url = get_contract_url('IdentityMgmt');
    $account = get_address_from_pk($adminpk);
    $contractabi = get_contract_abi('IdentityMgmt');
    $contractadress = get_contract_address('IdentityMgmt');
    $chainid = 10; // TODO: into settings.

    $web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
    $eth = $web3->eth;

    $contract = new Contract($web3->provider, $contractabi);
    $contract->at($contractadress);

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
            'nonce' => '0x'.dechex($nonce),
            'to' => $contractadress,
            'gas' => dechex(450),
            'data' => '0x'.$functiondata,
            'chainId' => $chainid
    ));

    $signedtransaction = $transaction->sign($adminpk);

    $eth->sendRawTransaction('0x'.$signedtransaction, function ($err, $tx) {
        if ($err !== null) {
            throw $err;
        }
    });
}

/**
 * Checks if the given $address belongs to a registered certifier.
 *
 * @param string $address
 * @return boolean True if $address blongs to a registered certifier, else false.
 */
function is_accredited_certifier($address) {
    $certifier = false;

    $web3 = new Web3(new HttpProvider(new HttpRequestManager(get_contract_url('IdentityMgmt'), 30)));

    $contract = new Contract($web3->provider, get_contract_abi('IdentityMgmt'));
    $contract->at(get_contract_address('IdentityMgmt'));
    $contract->call('isAccreditedCertifier', $address, function ($err, $result) use (&$certifier) {
        if ($err !== null) {
            throw $err;
        }
        if ($result) {
            $certifier = $result[0];
        }
    });
    return $certifier;
}

/**
 * Unregisteres a certifier identified by $useraddress on the bockchain with the given privatekey $adminpk.
 *
 * @param string $useraddress
 * @param string $adminpk
 * @return void
 */
function remove_certifier_from_blockchain($useraddress, $adminpk) {
    $url = get_contract_url('IdentityMgmt');
    $account = get_address_from_pk($adminpk);
    $contractabi = get_contract_abi('IdentityMgmt');
    $contractadress = get_contract_address('IdentityMgmt');
    $chainid = 10; // TODO: into settings.

    $web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
    $eth = $web3->eth;

    $contract = new Contract($web3->provider, $contractabi);
    $contract->at($contractadress);

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
            'nonce' => '0x'.dechex($nonce),
            'to' => $contractadress,
            'gas' => dechex(450),
            'data' => '0x'.$functiondata,
            'chainId' => $chainid
    ));
    $signedtransaction = $transaction->sign($adminpk);

    $eth->sendRawTransaction('0x'.$signedtransaction, function ($err, $tx) {
        if ($err !== null) {
            throw $err;
        }
    });
}

/**
 * Get the blockchain address belonging to a given privatekey $pk.
 *
 * @param string $pk
 * @return string
 */
function get_address_from_pk($pk) {
    $util = new Util();
    $publickey = $util->privateKeyToPublicKey($pk);
    $address = $util->publicKeyToAddress($publickey);
    return $address;
}

/**
 * Get the blockchain address of the institution a certifier identified by $address
 *
 * @param string $address
 * @return string
 */
function get_institution_from_certifier($address) {
    $institutionaddress = false;

    $web3 = new Web3(new HttpProvider(new HttpRequestManager(get_contract_url('IdentityMgmt'), 30)));

    $contract = new Contract($web3->provider, get_contract_abi('IdentityMgmt'));
    $contract->at(get_contract_address('IdentityMgmt'));
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
 * Get pending transaction on the blochchain.
 *
 * @return void
 */
function get_pending_transactions() {
    $url = get_contract_url('IdentityMgmt');
    $web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
    $eth = $web3->eth;
    $eth->getPendingTransactions(function ($err, $data) {
        if ($err !== null) {
            throw $err;
        }
        var_dump($data);
    });
}
