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
 * @copyright   2020 ILD TH Lübeck <support@oncampus.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Digital certificate';
$string['modulenameplural'] = 'Digital certificate';
$string['pluginname'] = 'Digital certificate';
$string['pluginadministration'] = 'Plugin Administration';

$string['ilddigitalcertname'] = 'Name';

$string['add_certifier'] = 'Add certifier';
$string['block_heading'] = 'Digital certificates in the blockchain';
$string['block_summary'] = '<p>Verify the authenticity of your digital certificates here.</ p>
<p>Simply drag and drop your certificate (PDF or BCRT file) into the field.</ p>
<p>You can check your printed version of the certificate by scanning the QR code below.
</p>';
$string['certhash'] = 'Zertifikat-Hash';
$string['certificate'] = 'Certificate';
$string['certificate_overview'] = 'All certificates in this course';
$string['certifier_address'] = 'Blockchain address of the certifier';
$string['choose'] = 'Choose';
$string['configlabel_blockchain_url'] = 'Blockchain URL';
$string['configlabel_CertMgmt_address'] = 'Smart-Contract-Adresse CertMgmt';
$string['configlabel_custom_menu_entry'] = 'Entry in custom menu';
$string['configlabel_failover_url'] = 'Alternative Blockchain URL';
$string['configlabel_IdentityMgmt_address'] = 'Smart-Contract-Adresse IdentityMgmt';
$string['configlabel_max_token_age'] = 'Max token age';
$string['configdesc_blockchain_url'] = 'Diese Adresse wird benötigt um die Verbindung zu einem geeigneten Blockchain-Knoten herzustellen.';
$string['configdesc_CertMgmt_address'] = 'Dies ist die Adresse des Smart Contracts für die Verwaltung der Zertifikate in der Blockchain.';
$string['configdesc_custom_menu_entry'] = 'Fügt einen Eintrag im Nutzermenü hinzu, der zur persönlichen Zertifikatsübersicht führt.';
$string['configdesc_failover_url'] = 'Diese URL wird verwendet, wenn die primäre Blockchain URL nicht erreichbar ist.';
$string['configdesc_IdentityMgmt_address'] = 'Dies ist die Adresse des Smart Contracts für die Verwaltung der Benutzerrollen in der Blockchain.';
$string['configdesc_max_token_age'] = 'Diese Dauer gibt an nach welcher Zeit der Link zum Generieren eines Privat Keys seine Gültigkeit verliert.';
$string['criteria'] = 'Criteria';
$string['data'] = 'Certificate data';
$string['delete_certifier'] = 'Aus Blockchain entfernen';
$string['descconfig'] = 'Information about the issuer';
$string['description'] = 'Description';
$string['drag_n_drop'] = 'Click here or drag and drop to upload file';
$string['edit_certifiers'] = 'Manage certifiers';
$string['edit_issuers'] = 'Aussteller verwalten';
$string['error_choose'] = 'Choose issuer';
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
$string['generate_pk'] = 'Link zum generieren des Private Key per E-Mail versenden';
$string['headerconfig'] = 'Issuer';
$string['html'] = 'Certificate';
$string['image'] = 'Image';
$string['institution_pk'] = 'Private key of the issuer';
$string['invalid'] = 'Invalid certificate';
$string['invalid_format'] = 'Invalid file format';

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

$string['json'] = 'JSON';
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
$string['No_institution_found_in_IPFS'] = 'No issuer information found in IPFS';
$string['no_pref_found'] = 'No user preference "mod_ilddigitalcert_certifier" found for user {$a->fullname}. 
Please check if you are logged in correctly.';
$string['only_blockchain'] = 'Nur Zertifikate, die in der Blockchain registriert sind';
$string['only_nonblockchain'] = 'Nur Zertifikate, die nicht in der Blockchain registriert sind';
$string['overview'] = 'Overview';
$string['overview_intro'] = 'Hier sehen Sie eine Übersicht über alle erworbenen Zertifikate aus allen Kursen, in die Sie eingeschrieben sind oder waren.';
$string['pdf'] = 'PDF';
$string['preview'] = 'Vorschau für das Zertifikat';
$string['recipient'] = 'Recipient';
$string['registered_and_signed'] = 'Das Zertifikat ist in der Blockchain registriert und signiert';
$string['select_user'] = 'Wählen Sie eine/n Nutzer/in aus!';
$string['settings_descconfig'] = '<p>Diese Parameter werden benötigt um eine Verbindung zur Blockchain aufzubauen und 
um die Smart Contracts auszuführen, die verwendet werden um Zertifikate und Zertifizierer zu verwalten.</p>
<p><strong>Es können einfach die voreingestellten Default-Parameter verwendet werden um die DigiCerts Blockchain zu benutzen.</strong></p>
<p>Nähere Informationen finden Sie hier <a href="https://www.digicerts.de">DigiCerts</a>.</p>';
$string['settings_headerconfig'] = 'Blockchain Einstellungen';
$string['settings_headerconfig_general'] = 'Allgemeine Einstellungen';
$string['sign_cert'] = 'Zertifikat in Blockchain registrieren und signieren';
$string['sign_delete_certifier_with_pk'] = 'Um den Zertifizierer aus der Blockchain zu entfernen, muss dieser Vorgang mit dem Private Key einer Zertifizierungsstelle signiert werden.';
$string['sign_add_certifier_with_pk'] = 'Hinzufügen eines neuen Zertifizierers mit Private Key signieren';
$string['sign_with_pk'] = 'Um das Zertifikat in der Blockchain zu registrieren, muss dieser Vorgang mit dem dazugehörigen Private Key signiert werden.';
$string['startdate'] = 'Date of issue';
$string['subject_generate_pk'] = 'Generieren Sie Ihren Private Key als Zertifizierer';
$string['template'] = 'HTML Template';
$string['title'] = 'Certificate';
$string['toblockchain'] = 'Register and sign in blockchain';
$string['unknown'] = 'Certificate could not be found in the Blockchain';
$string['upload'] = 'Upload certificate';
$string['upload_again'] = 'Upload new certificate';
$string['use_address'] = 'Bereits vorhandene Blockchain Adresse eines Zertifizierers verwenden';
$string['valid'] = 'Valid certificate';
$string['validation'] = 'Valid';
$string['verify'] = 'Zertifikate überprüfen';
$string['verify_authenticity'] = 'Echtheit überprüfen';
$string['verify_authenticity_descr'] = 'In die PDF Version dieses Zertifikates ist eine Datei im JSON-Format eingebettet. 
Deren Hashwert ist in der Zertifikats-Blockchain gespeichert. So lässt sich jederzeit die Echtheit 
des Zertifikats überprüfen. Laden Sie dazu die PDF-Datei unter der 
URL <a href="{$a->url}">{$a->url}</a> hoch. 
<br />Ihre ausgedruckte Version des Zertifikates können Sie überprüfen indem Sie dort den Hashwert 
eingeben oder nebenstehenden QR-Code einscannen. 
<br />HASH: <a href="{$a->url}?hash={$a->hash}">{$a->hash}</a>';
$string['verify_description'] = 'Verify your digital certificates here in the blockchain.';
$string['waiting_for_pk_generation'] = 'Waiting for user to generate private key';
$string['waiting_for_registration'] = 'Waiting for registration in Blockchain';
