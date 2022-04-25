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
 * Plugin strings are defined here.
 *
 * @package     mod_ilddigitalcert
 * @category    string
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Digital certificate';
$string['modulenameplural'] = 'Digital certificate';
$string['pluginname'] = 'Digital certificate';
$string['pluginadministration'] = 'Plugin Administration';

$string['ilddigitalcertname'] = 'Name';

$string['add_certifier'] = 'Add certifier';

$string['automation'] = 'Automation';
$string['automation_help'] = 'If automation is active, certificates will be signed and written to the blockchain automatically by the selected certifier.';
$string['automation_report:contexturlname'] = 'Manage signed certificates';
$string['automation_report:enable'] = 'Weekly report';
$string['automation_report:enable_help'] = 'If this option is enabled the set certifier will receive a report at the end of every week that lists all the certificates that were signed in their name during the past week.';
$string['automation_report:end'] = '';
$string['automation_report:intro'] = '<p>Hello {$a},</p>
<p>following certificates were recently signed automatically in your name and written to the blockchain successfully:</p>';
$string['automation_report:other_certs'] = 'Other certificates';
$string['automation_report:subject'] = 'Automatically signed certificates';

$string['auto_certifier'] = 'Automatic certifier';
$string['auto_certifier_help'] = 'Specify the certifier, on whose beahlf the certificates will be automatically signed.';
$string['auto_pk'] = 'Private key of certifier';
$string['auto_pk_help'] = 'Set the private key of the selected certifier here.';
$string['auto_certifier_help'] = 'Specify the certifier, on whose beahlf the certificates will be automatically signed.';
$string['block_heading'] = 'Digital certificates in the blockchain';
$string['block_summary'] = '<p>Verify the authenticity of your digital certificates here.</ p>
<p>Simply drag and drop your certificate (PDF, XML or BCRT file) into the field.</ p>
<p>You can check your printed version of the certificate by scanning the QR code below.
</p>';
$string['body_new_attribute'] = 'With this message you received a new attribute to complete your personal data.';
$string['cert_file_description'] = 'PDF certificate file with embedded certificate metadata.';
$string['cert_waiting_for_registration'] = 'This certificate is waiting to be signed by an accredited certifier and to be registered in the blockchain.';
$string['certhash'] = 'Certificate hash';
$string['certificate'] = 'Certificate';
$string['certifier'] = 'Certifier';
$string['certifier_address'] = 'Blockchain address of the certifier';
$string['choose'] = 'Choose';
$string['configlabel_blockchain_url'] = 'Blockchain URL';
$string['configlabel_CertMgmt_address'] = 'Smart-Contract-Address CertMgmt';
$string['configlabel_custom_menu_entry'] = 'Entry in custom menu';
$string['configlabel_failover_url'] = 'Alternative Blockchain URL';
$string['configlabel_IdentityMgmt_address'] = 'Smart-Contract-Adresse IdentityMgmt';
$string['configlabel_max_token_age'] = 'Max token age';
$string['configlabel_demo_mode'] = 'Enable demo mode';
$string['configdesc_blockchain_url'] = 'This address is needed to establish a connection to a suitable blockchain node.';
$string['configdesc_CertMgmt_address'] = 'Address of the smart contract for the management of certificates in the blockchain.<br/>
Address of the current (2020-09-28) contract: 0x8a7e3622D3f200aBb1B00D25126f86256c7368dB';
$string['configdesc_custom_menu_entry'] = 'Adds an entry in the custom usermenu, that links to the personal certifiacte overview.';
$string['configdesc_failover_url'] = 'This url will be used if the primary blockchain url is not reachable.';
$string['configdesc_IdentityMgmt_address'] = 'Address of the smart contract for the management of user roles in the blockchain.<br/>
Address of the current (2020-09-28) contract: 0xF40ec6b07009de471F3E2773b276F434F2c1c567';
$string['configdesc_max_token_age'] = 'This duration specifies how long the link for generating a private key will be valid.';
$string['configdesc_demo_mode'] = 'For demo or testing purposes enable this option. Certificates will be written to a demo blockchain that is not meant for production use. Please do not use personal data while this option is enabled.';
$string['criteria'] = 'Criteria';
$string['data'] = 'Certificate data';
$string['dcxapikey'] = 'API key';
$string['dcconnectorid'] = 'Connector id';
$string['dcconnectorsettings'] = 'Connector settings';
$string['dcconnector_pdfuploaddesc'] = 'PDF certificate file with embedded certificate metadata.';
$string['dchost'] = 'Domain';
$string['delete_certifier'] = 'Remove from blockchain';
$string['descconfig'] = 'Information about the issuer';
$string['description'] = 'Description';
$string['drag_n_drop'] = 'Click here or drag and drop to upload file';
$string['edci'] = 'EDCI';
$string['edit_certifiers'] = 'Manage certifiers';
$string['edit_issuers'] = 'Manage certifiers';
$string['error_choose'] = 'Choose issuer';
$string['error_choose_certifier'] = 'Choose certifier';
$string['error_register_cert'] = 'Error during registration in the blockchain';
$string['error_revoke_cert'] = 'Error during revocation';
$string['error_novalidblockchainurl'] = 'No valid or active blockchain url could be found. Please check the plugin settings and enter a valid blockchain url.';
$string['examination_end'] = 'Examniation end';
$string['examination_place'] = 'Examniation place';
$string['examination_start'] = 'Examination start';
$string['examination_regulations'] = 'Examination regulations';
$string['examination_regulations_url'] = 'Examination regulations url';
$string['examination_regulations_id'] = 'Examination regulations version';
$string['examination_regulations_date'] = 'Examination regulations date';
$string['expertise'] = 'Competences';
$string['expiredate'] = 'Expire date';
$string['expireperiod'] = 'Expire period';
$string['extractmetadata'] = 'Extract metadata';
$string['file_upload_error'] = 'Error while sending file';
$string['generate_adr_from_pk'] = 'Generate blockchain adress and private key';
$string['generatehash'] = 'Generate hash';
$string['generate_pk'] = 'Send E-Mail including a link for generating a private key';
$string['headerconfig'] = 'Issuer';
$string['html'] = 'Certificate';
$string['image'] = 'Image';
$string['institution_pk'] = 'Private key of the issuer';
$string['invalid'] = 'Invalid certificate';
$string['invalid_format'] = 'Invalid file format';
$string['invalid_hash_format'] = 'Invalid hash format';
$string['invalid_pk_format'] = 'The given private key is invalid';

$string['issued'] = 'The certificate was issued, but still needs to be signed and registered in the blockchain.';
$string['issuedcerts_report:contexturlname'] = 'Sign issued certificates';
$string['issuedcerts_report:end'] = '';
$string['issuedcerts_report:intro'] = '<p>Hello {$a},</p>
<p>following certificates were recently issued, but still need to be signed:</p>';
$string['issuedcerts_report:nocertifierincourse'] = 'Attention, at the moment there is no certifier enrolled in the course {$a}. Only certifiers are able to sign and register issued certificates in the blockchain. Please enrole a certifier in the course or consider contacting for further help.';
$string['issuedcerts_report:other_certs'] = 'Other certificates';
$string['issuedcerts_report:subject'] = 'Issued certificates waiting to be signed';

$string['issuer'] = 'Issuer';
$string['issueraddress'] = 'Blockchainaddress of the issuing institution';
$string['issuername_label'] = 'Name';
$string['issuername_descr'] = 'Name of the issuer';

$string['issuerpob'] = 'Pob';
$string['issuerstreet'] = 'Street';
$string['issuerzip'] = 'Zipcode';
$string['issuerlocation'] = 'City';
$string['issuerurl'] = 'URL';
$string['issueremail'] = 'E-Mail';
$string['issuerdescription'] = 'Description';


$string['json'] = 'Metadata';
$string['subject_new_attribute'] = 'New attribute';
$string['subject_certificate_revoked'] = 'Your Certificate was revoked';
$string['subject_new_certificate'] = 'New digital certificate';
$string['subject_new_digital_certificate'] = 'New digital certificate in the blockchain';
$string['messageprovider:ilddigitalcert_issuedcerts_report'] = 'Recently issued certificates';
$string['messageprovider:ilddigitalcert_automation_report'] = 'Automatically signed certificates';
$string['message_certificate_revoked'] = '<p>Hi {$a->fullname},</p>
<p>Your certificate was revoked.</p>
<p>Here you can view your certificate: <a href="{$a->url}">{$a->url}</a>.</p>
<p>Kind regards</p>
<p>Your {$a->from} team</p>';
$string['message_new_certificate_html'] = '<p>Hello {$a->fullname},</p>
<p>You have received a digital certificate.</p>
<p>Here you can view your certificate: <a href="{$a->url}">{$a->url}</a>.</p>
<p>Kind regards</p>
<p>Your {$a->from} team</p>';
$string['message_new_digital_certificate_html'] = '<p>Hello {$a->fullname},</p>
<p>Your digital certificate was signed and registered in the blockchain.</p>
<p>Here you can view your certificate: <a href="{$a->url}">{$a->url}</a>.</p>
<p>Kind regards</p>
<p>Your {$a->from} team</p>';
$string['message_generate_pk'] = 'Hello {$a->fullname},

You were added as a new certifier on {$a->url}

To sign and register digital certificates in the blockchain you need a private key.

You can generate your own private key by following this link.

{$a->token_link}

Kind regards
Your {$a->from} team';
$string['message_html_generate_pk'] = '<p>Hello {$a->fullname},</p>
<p>&nbsp;</p>
<p>You were added as a new certifier on {$a->url}</p>
<p>&nbsp;</p>
<p>To sign and register digital certificates in the blockchain you need a private key.</p>
<p>&nbsp;</p>
<p>You can generate your own private key by following this link.</p>
<p>&nbsp;</p>
<p><a href="{$a->token_link}">{$a->token_link}</a></p>
<p>&nbsp;</p>
<p>Kind regards</p>
<p>Your {$a->from} team</p>';
$string['message_sendtowallet_subject'] = 'Your digital certificate';
$string['message_sendtowallet_body'] = 'Attached to this message you receive your digital certificate. You can also find your certificate in the folder "Files".';
$string['missingcertid'] = 'Certificate id is missing';
$string['msg_send_error'] = 'Error while sending message';
$string['new_certifier_message'] = 'Hello {$a->fullname}

You were added as a new certifier for the issuing institution {a->institution} on {$a->url}

Kind regards
{$a->from}';
$string['new_certifier_message_html'] = 'Hello {$a->fullname}

You were added as a new certifier for the issuing institution {a->institution} on {$a->url}

Kind regards
{$a->from}';
$string['new_certifier_message_pk'] = 'Hello {$a->fullname}

You were added as a new certifier for the issuing institution {a->institution} on {$a->url}

Kind regards
{$a->from}';
$string['new_certifier_message_pk_html'] = 'Hello {$a->fullname}

You were added as a new certifier for the issuing institution {a->institution} on {$a->url}

Kind regards
{$a->from}';
$string['new_certifier_subject'] = 'You were added as a certifier';
$string['new_pk_generated'] = '<p>Congratulations!</p>
<p>A new private key was issued for you.</p>
<p>Your private key: {$a->pk}</p>
<p>Treat your private key as an important password and never pass it on to another person.</p>
<p>With your private key you can now sign and register digital certificates in the blockchain.</p>
<p>Do not close or reload this page before you have\'nt stored your private key safely.</p>';
$string['no_certifier'] = 'No suitable certifiers found';
$string['No_institution_found_in_IPFS'] = 'No issuer information found in IPFS';
$string['no_pref_found'] = 'No user preference "mod_ilddigitalcert_certifier" found for user {$a->fullname}.
Please check if you are logged in correctly.';
$string['not_installed_correctly'] = 'The plugin "Digital Certificate" was not installed completely.
Please read the file README.md or contact the site administrator.';
$string['not_logged_in'] = 'You are not logged in.';
$string['only_blockchain'] = 'Registered certificates';
$string['only_nonblockchain'] = 'Unregistered certificates';
$string['overview'] = 'Your awarded certificates';
$string['overview_certifier'] = 'Overview of issued certificates';
$string['overview_course'] = 'Issued Certificates in course "{$a}"';
$string['pdf'] = 'PDF';
$string['preview'] = 'Preview the certificate';
$string['recipient'] = 'Recipient';
$string['reissue'] = 'Reissue';
$string['reissue_confirmation'] = 'Do you want to reissue following certificates?';
$string['reissue_error_already_signed'] = 'Couldn\'t reissue {$a} certificat(s), because they where already signed and registered in the blockchain.';
$string['reissue_success'] = 'Susscessfully reissued certificate for: <b>{$a}</b>';
$string['registered_and_signed'] = 'The certificate was successfully signed and registered in the blockchain.';
$string['revoke'] = 'Revoke';
$string['revoked'] = 'The certificate was successfully revoked.';
$string['revoke_confirmation'] = 'Do you want to revoke following certificates?';
$string['revoke_error_invalid'] = '{$a} certificate(s) could\'nt be revoked, because they are not yet registered in the blockchain.';
$string['scan_qr_code'] = 'To send your digital certificate to your wallet, you have to establish a connection first. To do this, open the app and scan the qr-code. Afterwards follow the instructions in the App.';
$string['select_user'] = 'Select a user!';
$string['send_automation_report'] = 'Reports recently and automatically signed certs.';
$string['send_issuedcerts_report'] = 'Reports issued certs waiting to be signed.';
$string['send_to_wallet'] = 'Send to wallet';
$string['send_certificate_to_wallet'] = 'Send the digital certificate to your wallet. Afterwards you can find it in your app on your smartphone and share it with other institutions.';
$string['send_certificate_to_wallet_success'] = 'The certificate has been sent to your wallet. You can find it in your app on your smartphone.';
$string['settings_descconfig'] = '<pThese parameters are needed to establish a connection to the blockchain and
to execute the smart contracts that are used to manage certificates and certifiers.</p>
<p><strong>The default parameters can easily be used to use the DigiCerts-blockchain.</strong></p>
<p>For further informations visit <a href="https://www.digicerts.de">DigiCerts</a>.</p>';
$string['settings_headerconfig'] = 'Blockchain settings';
$string['settings_headerconfig_general'] = 'General settings';
$string['sign_add_certifier_with_pk'] = 'Sign with your private key to execute adding a certifier';
$string['sign_cert'] = 'Sign and register certificate in the blockchain';
$string['sign_confirmation'] = 'Do you want to sing and register following certificates?';
$string['sign_delete_certifier_with_pk'] = 'To remove the certifier from the blockchain, this action has to be signed by the private key of an issuing institution.';
$string['sign_error_already_signed'] = 'Couldn\'t sign {$a} certificat(s), because they where already signed and registered in the blockchain.';
$string['sign_with_pk'] = 'To register a certificate in the blockchain the certificate has to be signed with the private key of a qualified certifier.';
$string['startdate'] = 'Date of issue';
$string['study_field'] = 'Study field';
$string['subject_generate_pk'] = 'Generate your private key to become a certifier';
$string['template'] = 'HTML Template';
$string['title'] = 'Certificate';
$string['toblockchain'] = 'Register and sign';
$string['to_many_enrolments'] = 'Because of multiple parallel enrolements in this course the digital certificate couldn\'t be issued.';
$string['unknown'] = 'Certificate could not be found in the Blockchain';
$string['upload'] = 'Upload certificate';
$string['upload_again'] = 'Upload new certificate';
$string['use_address'] = 'Use an already existing blochchain address of a certifier';
$string['valid'] = 'Valid certificate';
$string['validation'] = 'Valid';
$string['validfrom'] = 'Valid from';
$string['validuntil'] = 'Valid until';
$string['verify'] = 'Verify certificate';
$string['verify_authenticity'] = 'Verify authenticity';
$string['verify_authenticity_descr'] = 'To validate the authenticity of a certificate, upload the PDF-file at
<a href="{$a->url}">{$a->url}</a>.
<br />You can verify the authenticity of the printed out certificate, by scanning the nearby QR-Code.';
$string['verify_description'] = 'Verify your digital certificates here in the blockchain.';
$string['verify_hash'] = 'Verifiy hash';
$string['waiting_for_pk_generation'] = 'Waiting for user to generate private key';
$string['waiting_for_request'] = 'Waiting for request';
$string['waiting_for_registration'] = 'Waiting for registration in Blockchain';
$string['wrongcertidornotloggedin'] = 'Wrong certificate id';
$string['wrong_relationship'] = 'The connection between your wallet and the system is incorrect. Please contact an administrator.';
