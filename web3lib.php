<?php 
if (!file_exists('vendor/autoload.php')) {
	require_once(__DIR__.'/../../config.php');
	echo $OUTPUT->header();
	\core\notification::error(get_string('not_installed_correctly', 'mod_ilddigitalcert'));
	echo $OUTPUT->footer();
	die();
}
require('vendor/autoload.php');

use Web3\Web3;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Contract;
use Web3p\EthereumTx\Transaction;
use Web3p\EthereumUtil\Util;
use Web3\Contracts\Types\Bytes;
use phpseclib\Math\BigInteger;

global $account;
global $contract_names;
$contract_names = array('CertMgmt' 	  => 'CertificateManagement',
						'IdentityMgmt' => 'IdentityManagement');

function check_node($url) {
	$web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
	$success = false;
	$web3->eth->blockNumber(function ($err, $blockNumber) use (&$success) {
		if ($err === null) {
			$block = $blockNumber->value;
			if ($block > 0) {
				$success = true;
			}
		}
	});
	// TODO if (!$success) {throw error}
	return $success;
}

function get_contract_abi($contractname) {
	global $CFG, $contract_names;
	$contractname = $contract_names[$contractname];
	$filename = $CFG->wwwroot.'/mod/ilddigitalcert/contracts/'.$contractname.'.json';
	$contract = json_decode(file_get_contents($filename));
	return json_encode($contract->contract_abi); // quorum
}

function get_contract_address($contractname) {
	global  $contract_names;//, $CFG;
	$contractname = $contract_names[$contractname];
	//$filename = $CFG->wwwroot.'/mod/ilddigitalcert/contracts/'.$contractname.'.json';
	//$contract = json_decode(file_get_contents($filename));

	if ($contractname == $contract_names['CertMgmt']) {
		$CertMgmt_address = get_config('ilddigitalcert', 'CertMgmt_address');
		if (isset($CertMgmt_address) and $CertMgmt_address != '') {
			return $CertMgmt_address;
		}
	}
	elseif ($contractname == $contract_names['IdentityMgmt']) {
		$IdentityMgmt_address = get_config('ilddigitalcert', 'IdentityMgmt_address');
		if (isset($IdentityMgmt_address) and $IdentityMgmt_address != '') {
			return $IdentityMgmt_address;
		}
	}
}

function get_contract_url($contractname) {
	global $contract_names;//, $CFG;
	$contractname = $contract_names[$contractname];
	//$filename = $CFG->wwwroot.'/mod/ilddigitalcert/contracts/'.$contractname.'.json';
	//$contract = json_decode(file_get_contents($filename));
	
	$blockchain_url = get_config('ilddigitalcert', 'blockchain_url');
	$failover_url = get_config('ilddigitalcert', 'failover_url');
	// TODO mehrere failover-adressen mit komma getrennt möglich machen
	// check if node is working
	if (isset($blockchain_url) and $blockchain_url != '' and check_node($blockchain_url)) {
		return $blockchain_url;
	}
	elseif (isset($failover_url) and $failover_url != '' and check_node($failover_url)) {
		return $failover_url;
	}
	else {
		// TODO error
		return false;
	}
}

function store_certificate($hash, $startdate, $enddate, $pk) {
	$url = 				get_contract_url('CertMgmt');
	$account = 			get_address_from_pk($pk);
	$contract_abi = 	get_contract_abi('CertMgmt');
	$contract_adress = 	get_contract_address('CertMgmt');
	$storehash = 		$hash;
	//print_object($startdate);
	// TODO $startdate = 		new BigInteger($startdate,10);
	//print_object($startdate);
	// TODO $enddate   = 		new BigInteger($enddate,10);
	//$startdate = 		strval($startdate);
	//$enddate   = 		strval($enddate);
	$chainid = 			10; //quorum chainid 10 // TODO: in die Settings

	$web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
	$eth = $web3->eth;

	$contract = new Contract($web3->provider, $contract_abi);
	$contract->at($contract_adress);
	
	$hashes = new stdClass();
	$hashes->certhash = $hash;

	$nonce = 0;
	$r = $eth->getTransactionCount($account, 'latest', function ($err, $data)  use (&$nonce) {
		if ($err !== null) {
			throw $err;
		}
		$nonce = $data->toString();
		
	});

	$function_data = $contract->getData('storeCertificate', $storehash, $startdate, $enddate);

	$transaction = new Transaction(array(
			'from'=>$account,
			'nonce' => '0x'.dechex($nonce),
			'to' => $contract_adress,
			'gas' => dechex(450),
			'data' => '0x'.$function_data,
			'chainId' => $chainid
	));
	$signed_transaction = $transaction->sign($pk);

	$eth->sendRawTransaction('0x'.$signed_transaction, function ($err, $tx) use (&$hashes){
		if ($err !== null) {
			throw $err;
		}
		//print_object('tx hash: '.$tx);
		$hashes->txhash = $tx;
	});
	
	// TODO warum geht das nicht?
	/*
	$eth->getTransactionReceipt($hashes->txhash, function ($err, $receipt) {
		if ($err !== null) {
			throw $err;
		}
		print_object($receipt);
	});
	#*/

	// Prüfen ob Zertifikat auch in BC exisiert
	$start = time();
	while (1) {
		$now = time();
		$cert = getCertificate($hashes->certhash);
		//echo '<br />'.$cert->institution;
		if (isset($cert->valid) and $cert->valid == 1) {
			return $hashes;
		}
		if ($now - $start > 30) {
			print_object('Error:');
			print_object($hashes);
			unset($hashes->certhash);
			unset($hashes->txhash);
			break;
		}
	}
	return $hashes;
}

function revokeCertificate($certhash, $pk) {
	// TODO: implementieren und testen
	$url = 				get_contract_url('CertMgmt');
	$account = 			get_address_from_pk($pk);
	$contract_abi = 	get_contract_abi('CertMgmt');
	$contract_adress = 	get_contract_address('CertMgmt');
	$chainid = 			10; //quorum chainid 10 // TODO: in die Settings

	$web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
	$eth = $web3->eth;

	$contract = new Contract($web3->provider, $contract_abi);
	$contract->at($contract_adress);

	$nonce = 0;
	$r = $eth->getTransactionCount($account, 'latest', function ($err, $data)  use (&$nonce) {
		if ($err !== null) {
			throw $err;
		}
		$nonce = $data->toString();
		
	});

	$function_data = $contract->getData('revokeCertificate', $certhash);
	
	$transaction = new Transaction(array(
			'from'=> 		$account,
			'nonce' => 		'0x'.dechex($nonce),
			'to' => 		$contract_adress,
			'gas' => 		dechex(450),
			'data' => 		'0x'.$function_data,
			'chainId' => 	$chainid
	));
	$signed_transaction = $transaction->sign($pk);

	$eth->sendRawTransaction('0x'.$signed_transaction, function ($err, $tx) {
		if ($err !== null) {
			throw $err;
		}
	});

	$cert = getCertificate($certhash);
	if ($cert->onHold == 0) {
		return true;
	}
	else {
		return false;
	}
}

function getCertificate($certhash) {
	$cert = new stdClass();
	
	$web3 = new Web3(new HttpProvider(new HttpRequestManager(get_contract_url('CertMgmt'), 30)));
	
	$contract = new Contract($web3->provider, get_contract_abi('CertMgmt'));
	$contract->at(get_contract_address('CertMgmt'));
	$contract->call('getCertificate', $certhash, function ($err, $result) use ($cert) {
		if ($err !== null) {
			throw $err;
		}
		if ($result) {
			//print_object($result);
			/*
			Array
			(
				[0] => 0xe53d1b462e5d4dbda8edf1bf756856fc526a9f23
				[1] => 0xaedd1f3f6c7b0f169c99bfaaff4d33962952bc18ecba40aad10ff5f2cf2db851
				[2] => 0xe53d1b462e5d4dbda8edf1bf756856fc526a9f23
				[3] => 0xaedd1f3f6c7b0f169c99bfaaff4d33962952bc18ecba40aad10ff5f2cf2db851
				[4] => Array
					(
						[0] => phpseclib\Math\BigInteger Object
							(
								[value] => 0x5e596496
								[engine] => gmp
							)

						[1] => phpseclib\Math\BigInteger Object
							(
								[value] => 0x621c0270
								[engine] => gmp
							)

					)

				[5] => phpseclib\Math\BigInteger Object
					(
						[value] => 0x
						[engine] => gmp
					)

				[6] => 1
			)
			*/
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

function add_certifier_to_blockchain($user_address, $admin_pk) {
	$url = 				get_contract_url('IdentityMgmt');
	$account = 			get_address_from_pk($admin_pk);
	$contract_abi = 	get_contract_abi('IdentityMgmt');
	$contract_adress = 	get_contract_address('IdentityMgmt');
	$chainid = 			10; //quorum chainid 10 // TODO: Settings
	
	$web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
	$eth = $web3->eth;

	$contract = new Contract($web3->provider, $contract_abi);
	$contract->at($contract_adress);

	$nonce = 0;
	$r = $eth->getTransactionCount($account, 'latest', function ($err, $data)  use (&$nonce) {
		if ($err !== null) {
			throw $err;
		}
		$nonce = $data->toString();
		
	});

	$function_data = $contract->getData('registerCertifier', $user_address);
	
	$transaction = new Transaction(array(
			'from'=> 		$account,
			'nonce' => 		'0x'.dechex($nonce),
			'to' => 		$contract_adress,
			'gas' => 		dechex(450),
			'data' => 		'0x'.$function_data,
			'chainId' => 	$chainid
	));

	$signed_transaction = $transaction->sign($admin_pk);

	$eth->sendRawTransaction('0x'.$signed_transaction, function ($err, $tx) {
		if ($err !== null) {
			throw $err;
		}

		//print_object($tx);
	});
}

function is_accredited_certifier($address) {
	//isAccreditedCertifier
	$certifier = false;
	
	$web3 = new Web3(new HttpProvider(new HttpRequestManager(get_contract_url('IdentityMgmt'), 30)));
	
	$contract = new Contract($web3->provider, get_contract_abi('IdentityMgmt'));
	$contract->at(get_contract_address('IdentityMgmt'));
	$contract->call('isAccreditedCertifier', $address, function ($err, $result) use (&$certifier) {
		if ($err !== null) {
			throw $err;
		}
		if ($result) {
			//print_object($result);
			$certifier = $result[0];
		}
	});
	//print_object($certifier);
	return $certifier;
}

function remove_certifier_from_blockchain($user_address, $admin_pk) {
	$url = 				get_contract_url('IdentityMgmt');
	$account = 			get_address_from_pk($admin_pk);
	$contract_abi = 	get_contract_abi('IdentityMgmt');
	$contract_adress = 	get_contract_address('IdentityMgmt');
	$chainid = 			10; //quorum chainid 10 // TODO: Settings

	$web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
	$eth = $web3->eth;

	$contract = new Contract($web3->provider, $contract_abi);
	$contract->at($contract_adress);

	$nonce = 0;
	$r = $eth->getTransactionCount($account, 'latest', function ($err, $data)  use (&$nonce) {
		if ($err !== null) {
			throw $err;
		}
		$nonce = $data->toString();
		
	});
	$function_data = $contract->getData('removeCertifier', $user_address);
	
	$transaction = new Transaction(array(
			'from'=> 		$account,
			'nonce' => 		'0x'.dechex($nonce),
			'to' => 		$contract_adress,
			'gas' => 		dechex(450),
			'data' => 		'0x'.$function_data,
			'chainId' => 	$chainid
	));
	$signed_transaction = $transaction->sign($admin_pk);

	$eth->sendRawTransaction('0x'.$signed_transaction, function ($err, $tx) {
		if ($err !== null) {
			throw $err;
		}
	});
}

function get_address_from_pk($pk) {
	$util = new Util();
	$public_key = $util->privateKeyToPublicKey($pk);
	$address = $util->publicKeyToAddress($public_key);
	return $address;
}

function get_institution_from_certifier($address) {
	// getInstitutionFromCertifier
	$institution_address = false;
	
	$web3 = new Web3(new HttpProvider(new HttpRequestManager(get_contract_url('IdentityMgmt'), 30)));
	
	$contract = new Contract($web3->provider, get_contract_abi('IdentityMgmt'));
	$contract->at(get_contract_address('IdentityMgmt'));
	$contract->call('getInstitutionFromCertifier', $address, function ($err, $result) use (&$institution_address) {
		if ($err !== null) {
			throw $err;
		}
		if ($result) {
			//print_object($result);
			$institution_address = $result[0];
		}
	});
	//print_object($certifier);
	return $institution_address;
}

function get_pending_transactions() {
	$url = 	get_contract_url('IdentityMgmt');
	$web3 = new Web3(new HttpProvider(new HttpRequestManager($url, 30)));
	$eth = $web3->eth;
	$eth->getPendingTransactions(function ($err, $data) {
		if ($err !== null) {
			throw $err;
		}
		print_object($data);
	});
}