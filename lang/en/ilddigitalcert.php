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
$string['automation_report:subject'] = 'Automatically signed certificates';
$string['automation_report:intro'] = '<p>Hello {$a},</p>
<p>following certificates were recently signed automatically and written to the blockchain successfully:</p>';
$string['automation_report:other_certs'] = 'Other certificates';
$string['automation_report:end'] = '';

$string['auto_certifier'] = 'Automatic certifier';
$string['auto_certifier_help'] = 'Specify the certifier, on whose beahlf the certificates will be automatically signed.';
$string['auto_pk'] = 'Private key of certifier';
$string['auto_pk_help'] = 'Set the private key of the selected certifier here.';
$string['auto_certifier_help'] = 'Specify the certifier, on whose beahlf the certificates will be automatically signed.';
$string['block_heading'] = 'Digital certificates in the blockchain';
$string['block_summary'] = '<p>Verify the authenticity of your digital certificates here.</ p>
<p>Simply drag and drop your certificate (PDF or BCRT file) into the field.</ p>
<p>You can check your printed version of the certificate by scanning the QR code below.
</p>';
$string['cert_waiting_for_registration'] = 'Dieses Zertifikat wartet auf Registrierung und Signierung in der Blockchain durch einen akkreditierten Zertifizierer.';
$string['certhash'] = 'Zertifikat-Hash';
$string['certificate'] = 'Certificate';
$string['certificate_overview'] = 'All certificates in this course';
$string['certifier'] = 'Certifier';
$string['certifier_address'] = 'Blockchain address of the certifier';
$string['choose'] = 'Choose';
$string['configlabel_blockchain_url'] = 'Blockchain URL';
$string['configlabel_CertMgmt_address'] = 'Smart-Contract-Adresse CertMgmt';
$string['configlabel_custom_menu_entry'] = 'Entry in custom menu';
$string['configlabel_failover_url'] = 'Alternative Blockchain URL';
$string['configlabel_IdentityMgmt_address'] = 'Smart-Contract-Adresse IdentityMgmt';
$string['configlabel_max_token_age'] = 'Max token age';
$string['configlabel_demo_mode'] = 'Enable demo mode';
$string['configdesc_blockchain_url'] = 'Diese Adresse wird benötigt um die Verbindung zu einem geeigneten Blockchain-Knoten herzustellen.';
$string['configdesc_CertMgmt_address'] = 'Address of the smart contract for the management of certificates in the blockchain.<br/>
Address of the current (2020-09-28) contract: 0x8a7e3622D3f200aBb1B00D25126f86256c7368dB';
$string['configdesc_custom_menu_entry'] = 'Fügt einen Eintrag im Nutzermenü hinzu, der zur persönlichen Zertifikatsübersicht führt.';
$string['configdesc_failover_url'] = 'Diese URL wird verwendet, wenn die primäre Blockchain URL nicht erreichbar ist.';
$string['configdesc_IdentityMgmt_address'] = 'Address of the smart contract for the management of user roles in the blockchain.<br/>
Address of the current (2020-09-28) contract: 0xF40ec6b07009de471F3E2773b276F434F2c1c567';
$string['configdesc_max_token_age'] = 'Diese Dauer gibt an nach welcher Zeit der Link zum Generieren eines Privat Keys seine Gültigkeit verliert.';
$string['configdesc_demo_mode'] = 'For demo or testing purposes enable this option. Certificates will be written to a demo blockchain that is not meant for production use. Please do not use personal data while this option is enabled.';
$string['criteria'] = 'Criteria';
$string['data'] = 'Certificate data';
$string['dcxapikey'] = 'API key';
$string['dcconnectorid'] = 'Connector id';
$string['dcconnectorsettings'] = 'Connector settings';
$string['dchost'] = 'Domain';
$string['delete_certifier'] = 'Aus Blockchain entfernen';
$string['descconfig'] = 'Information about the issuer';
$string['description'] = 'Description';
$string['drag_n_drop'] = 'Click here or drag and drop to upload file';
$string['edci'] = 'EDCI';
$string['edit_certifiers'] = 'Manage certifiers';
$string['edit_issuers'] = 'Aussteller verwalten';
$string['error_choose'] = 'Choose issuer';
$string['error_choose_certifier'] = 'Choose certifier';
$string['error_register_cert'] = 'Fehler beim Speichern in der Blockchain';
$string['examination_end'] = 'Examniation end';
$string['examination_place'] = 'Examniation place';
$string['examination_start'] = 'Examination start';
$string['examination_regulations'] = 'Examination regulations';
$string['examination_regulations_url'] = 'Examination regulations url';
$string['examination_regulations_id'] = 'Examination regulations version';
$string['examination_regulations_date'] = 'Examination regulations date';
$string['expertise'] = 'Kenntnisse';
$string['expiredate'] = 'Expire date';
$string['expireperiod'] = 'Expire period';
$string['file_upload_error'] = 'Error while sending file';
$string['generate_adr_from_pk'] = 'Generate blockchain adress and private key';
$string['generate_pk'] = 'Link zum generieren des Private Key per E-Mail versenden';
$string['headerconfig'] = 'Issuer';
$string['html'] = 'Certificate';
$string['image'] = 'Image';
$string['institution_pk'] = 'Private key of the issuer';
$string['invalid'] = 'Invalid certificate';
$string['invalid_format'] = 'Invalid file format';
$string['invalid_pk_format'] = 'The given private key is invalid';

$string['issuedcerts_report:subject'] = 'Issued certificates waiting to be signed';
$string['issuedcerts_report:intro'] = '<p>Hello {$a},</p>
<p>following certificates were recently issued, but still need to be signed:</p>';
$string['issuedcerts_report:other_certs'] = 'Other certificates';
$string['issuedcerts_report:end'] = '';

$string['issuer'] = 'Issuer';
$string['issueraddress'] = 'Blockchainadresse der Zertifizierungsstelle';
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
$string['subject_new_certificate'] = 'Neues digitales Zertifikat';
$string['subject_new_digital_certificate'] = 'Neues digitales Zertifikat in der Blockchain';
$string['messageprovider:ilddigitalcert_issuedcerts_report'] = 'Recently issued certificates';
$string['messageprovider:ilddigitalcert_automation_report'] = 'Automatically signed certificates';
$string['message_new_certificate'] = 'Hallo {$a->fullname},

Sie haben ein digitales Zertifikat erhalten.

Hier können Sie sich Ihr Zertifikat ansehen: {$a->url}.

Viele Grüße
Ihr {$a->from} Team';
$string['message_new_certificate_html'] = '<p>Hallo {$a->fullname},</p>
<p>Sie haben ein digitales Zertifikat erhalten.</p>
<p>Hier können Sie sich Ihr Zertifikat ansehen: <a href="{$a->url}">{$a->url}</a>.</p>
<p>Viele Grüße</p>
<p>Ihr {$a->from} Team</p>';
$string['message_new_digital_certificate'] = 'Hallo {$a->fullname},

Ihr digitales Zertifikat wurde in der Blockchain registriert und signiert.

Hier können Sie sich Ihr Zertifikat ansehen: {$a->url}.

Viele Grüße
Ihr {$a->from} Team';
$string['message_new_digital_certificate_html'] = '<p>Hallo {$a->fullname},</p>
<p>Ihr digitales Zertifikat wurde in der Blockchain registriert und signiert.</p>
<p>Hier können Sie sich Ihr Zertifikat ansehen: <a href="{$a->url}">{$a->url}</a>.</p>
<p>Viele Grüße</p>
<p>Ihr {$a->from} Team</p>';
$string['message_generate_pk'] = 'Hallo {$a->fullname},

Sie wurden als neuer Zertifizierer auf {$a->url} hinzugefügt.

Um digitale Zertifikate in der Blockchain zu registrieren und zu signieren, benötigen Sie einen Private Key.

Ihren persönlichen Private Key können Sie unter folgendem Link generieren.

{$a->token_link}

Viele Grüße
Ihr {$a->from} Team';
$string['message_html_generate_pk'] = '<p>Hallo {$a->fullname},</p>
<p>&nbsp;</p>
<p>Sie wurden als neuer Zertifizierer auf {$a->url} hinzugefügt.</p>
<p>&nbsp;</p>
<p>Um digitale Zertifikate in der Blockchain zu registrieren und zu signieren, benötigen Sie einen Private Key</p>
<p>&nbsp;</p>
<p>Ihren persönlichen Private Key können Sie unter folgendem Link generieren.</p>
<p>&nbsp;</p>
<p><a href="{$a->token_link}">{$a->token_link}</a></p>
<p>&nbsp;</p>
<p>Viele Grüße</p>
<p>Ihr {$a->from} Team</p>';
$string['missingcertid'] = 'Certificate id is missing';
$string['msg_send_error'] = 'Error while sending message';
$string['new_certifier_message'] = 'Hallo {$a->fullname}

Sie wurden als neuer Zertifizierer für die Zertifizierungsstelle {$a->institution} auf {$a->url} hinzugefügt.

Viele Grüße
{$a->from}';
$string['new_certifier_message_html'] = 'Hallo {$a->fullname}

Sie wurden als neuer Zertifizierer für die Zertifizierungsstelle {$a->institution} auf {$a->url} hinzugefügt.

Viele Grüße
{$a->from}';
$string['new_certifier_message_pk'] = 'Hallo {$a->fullname}

Sie wurden als neuer Zertifizierer für die Zertifizierungsstelle {$a->institution} auf {$a->url} hinzugefügt.

Viele Grüße
{$a->from}';
$string['new_certifier_message_pk_html'] = 'Hallo {$a->fullname}

Sie wurden als neuer Zertifizierer für die Zertifizierungsstelle {$a->institution} auf {$a->url} hinzugefügt.

Viele Grüße
{$a->from}';
$string['new_certifier_subject'] = 'Sie wurden als Zertifizierer hinzugefügt';
$string['new_pk_generated'] = '<p>Herzlichen Glückwunsch!</p>
<p>Für Sie wurde ein neuer Private Key erstellt.</p>
<p>Ihr Private Key: {$a->pk}</p>
<p>Behandeln Sie Ihren Private Key wie ein wichtiges Passwort und geben Sie ihn nicht weiter.</p>
<p>Mit Ihrem Private Key registrieren und signieren Sie Digitale Zertifikate in der Blockchain.</p>
<p>Schließen Sie diese Seite nicht und laden Sie sie nicht neu, bevor Sie Ihren Private Key gesichert haben!</p>';
$string['no_certifier'] = 'No suitable certifiers found';
$string['No_institution_found_in_IPFS'] = 'No issuer information found in IPFS';
$string['no_pref_found'] = 'No user preference "mod_ilddigitalcert_certifier" found for user {$a->fullname}.
Please check if you are logged in correctly.';
$string['not_installed_correctly'] = 'Das Plugin "Digitale Zertifikate" wurde nicht vollständig installiert.
Lesen Sie die Datei README.md oder wenden Sie sich an den Moodle Administrator';
$string['not_logged_in'] = 'You are not logged in.';
$string['only_blockchain'] = 'Registered certificates';
$string['only_nonblockchain'] = 'Unregistered certificates';
$string['overview'] = 'Overview';
$string['overview_intro'] = 'Hier sehen Sie eine Übersicht über alle erworbenen Zertifikate aus allen Kursen, in die Sie eingeschrieben sind oder waren.';
$string['pdf'] = 'PDF';
$string['preview'] = 'Vorschau für das Zertifikat';
$string['recipient'] = 'Recipient';
$string['reissue'] = 'Reissue certificate';
$string['reissue_confirmation'] = 'Do you want to reissue following certificates?';
$string['reissue_error_already_signed'] = 'Couldn\'t reissue {$a} certificat(s), because they where already signed and registered in the blockchain.';
$string['reissue_success'] = 'Susscessfully reissued certificate for: <b>{$a}</b>';
$string['registered_and_signed'] = 'The certificate was successfully signed and registered in the blockchain.';
$string['scan_qr_code'] = 'To send your digital certificate to your wallet, you have to establish a connection first. To do this, open the app and scan the qr-code. Afterwards follow the instructions in the App.';
$string['select_user'] = 'Wählen Sie eine/n Nutzer/in aus!';
$string['send_automation_report'] = 'Reports recently and automatically signed certs.';
$string['send_issuedcerts_report'] = 'Reports issued certs waiting to be signed.';
$string['send_to_wallet'] = 'Send to wallet';
$string['send_certificate_to_wallet'] = 'Send the digital certificate to your wallet. Afterwards you can find it in your app on your smartphone and share it with other institutions.';
$string['send_certificate_to_wallet_success'] = 'The certificate has been sent to your wallet. You can find it in your app on your smartphone.';
$string['settings_descconfig'] = '<p>Diese Parameter werden benötigt um eine Verbindung zur Blockchain aufzubauen und
um die Smart Contracts auszuführen, die verwendet werden um Zertifikate und Zertifizierer zu verwalten.</p>
<p><strong>Es können einfach die voreingestellten Default-Parameter verwendet werden um die DigiCerts Blockchain zu benutzen.</strong></p>
<p>Nähere Informationen finden Sie hier <a href="https://www.digicerts.de">DigiCerts</a>.</p>';
$string['settings_headerconfig'] = 'Blockchain Einstellungen';
$string['settings_headerconfig_general'] = 'Allgemeine Einstellungen';
$string['sign_add_certifier_with_pk'] = 'Hinzufügen eines neuen Zertifizierers mit Private Key signieren';
$string['sign_cert'] = 'Sign and register certificate in the blockchain';
$string['sign_confirmation'] = 'Do you want to sing and register following certificates?';
$string['sign_delete_certifier_with_pk'] = 'Um den Zertifizierer aus der Blockchain zu entfernen, muss dieser Vorgang mit dem Private Key einer Zertifizierungsstelle signiert werden.';
$string['sign_error_already_signed'] = 'Couldn\'t sign {$a} certificat(s), because they where already signed and registered in the blockchain.';
$string['sign_with_pk'] = 'To register a certificate in the blockchain the certificate has to be signed with the private key of a qualified certifier.';
$string['startdate'] = 'Date of issue';
$string['subject_generate_pk'] = 'Generieren Sie Ihren Private Key als Zertifizierer';
$string['template'] = 'HTML Template';
$string['title'] = 'Certificate';
$string['toblockchain'] = 'Register and sign';
$string['to_many_enrolments'] = 'Das Zertifikat kann aufgrund mehrerer paralleler Einschreibungen in diesen Kurs nicht ausgestellt werden.';
$string['unknown'] = 'Certificate could not be found in the Blockchain';
$string['upload'] = 'Upload certificate';
$string['upload_again'] = 'Upload new certificate';
$string['use_address'] = 'Bereits vorhandene Blockchain Adresse eines Zertifizierers verwenden';
$string['valid'] = 'Valid certificate';
$string['validation'] = 'Valid';
$string['verify'] = 'Zertifikate überprüfen';
$string['verify_authenticity'] = 'Echtheit überprüfen';
$string['verify_authenticity_descr'] = 'Um die Echtheit des Zertifikates zu überprüfen, laden Sie die PDF-Datei unter
<a href="{$a->url}">{$a->url}</a> hoch.
<br />Ihre ausgedruckte Version des Zertifikates können Sie überprüfen indem Sie nebenstehenden QR-Code einscannen.';
$string['verify_description'] = 'Verify your digital certificates here in the blockchain.';
$string['waiting_for_pk_generation'] = 'Waiting for user to generate private key';
$string['waiting_for_request'] = 'Waiting for request';
$string['waiting_for_registration'] = 'Waiting for registration in Blockchain';
$string['wrongcertidornotloggedin'] = 'Wrong certificate id';
$string['wrong_relationship'] = 'The connection between your wallet and the system is incorrect. Please contact an administrator.';
