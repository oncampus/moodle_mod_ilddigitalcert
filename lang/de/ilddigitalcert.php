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

$string['modulename'] = 'Digitales Zertifikat';
$string['modulenameplural'] = 'Digitale Zertifikate';
$string['pluginname'] = 'Digitales Zertifikat';
$string['pluginadministration'] = 'Plugin Administration';

$string['ilddigitalcertname'] = 'Name';

$string['add_certifier'] = 'Zertifizierer hinzufügen';
$string['block_heading'] = 'Digitale Zertifikate in der Blockchain';
$string['block_summary'] = '<p>Überprüfe hier die Echtheit Deiner digitalen Zertifikate.</p>
<p>Ziehe dazu einfach Dein Zertifikat (PDF oder BCRT-Datei) per Drag and Drop in das Feld.</p>
<p>Deine ausgedruckte Version des Zertifikates kannst Du überprüfen indem Du
den untenstehenden QR-Code einscannst.
</p>';
$string['cert_waiting_for_registration'] = 'Dieses Zertifikat wartet auf Registrierung und Signierung in der Blockchain durch einen akkreditierten Zertifizierer.';
$string['certhash'] = 'Zertifikat-Hash';
$string['certificate'] = 'Zertifikat';
$string['certificate_overview'] = 'Alle Zertifikate im Kurs';
$string['certifier_address'] = 'Blockchain-Adresse des Zertifizierers';
$string['choose'] = 'Bitte wählen';
$string['configlabel_blockchain_url'] = 'Blockchain URL';
$string['configlabel_CertMgmt_address'] = 'Smart-Contract-Adresse CertMgmt';
$string['configlabel_custom_menu_entry'] = 'Eintrag im Nutzermenü';
$string['configlabel_failover_url'] = 'Alternative Blockchain URL';
$string['configlabel_IdentityMgmt_address'] = 'Smart-Contract-Adresse IdentityMgmt';
$string['configlabel_max_token_age'] = 'Maximales Tokenalter';
$string['configdesc_blockchain_url'] = 'Diese Adresse wird benötigt um die Verbindung zu einem geeigneten Blockchain-Knoten herzustellen.';
$string['configdesc_CertMgmt_address'] = 'Dies ist die Adresse des Smart Contracts für die Verwaltung der Zertifikate in der Blockchain.';
$string['configdesc_custom_menu_entry'] = 'Fügt einen Eintrag im Nutzermenü hinzu, der zur persönlichen Zertifikatsübersicht führt.';
$string['configdesc_failover_url'] = 'Diese URL wird verwendet, wenn die primäre Blockchain URL nicht erreichbar ist.';
$string['configdesc_IdentityMgmt_address'] = 'Dies ist die Adresse des Smart Contracts für die Verwaltung der Benutzerrollen in der Blockchain.';
$string['configdesc_max_token_age'] = 'Diese Dauer gibt an nach welcher Zeit der Link zum Generieren eines Privat Keys seine Gültigkeit verliert.';
$string['criteria'] = 'Kriterien';
$string['data'] = 'Zertifikatsdaten';
$string['delete_certifier'] = 'Aus Blockchain entfernen';
$string['descconfig'] = 'Angaben zur Zertifizierungsstelle';
$string['description'] = 'Beschreibung';
$string['drag_n_drop'] = 'Hier klicken oder Dateien in dieses Feld ziehen (Drag & Drop) um sie hochzuladen';
$string['edit_certifiers'] = 'Zertifizierer verwalten';
$string['edit_issuers'] = 'Aussteller verwalten';
$string['error_choose'] = 'Wählen Sie eine Zertifizierungsstelle';
$string['error_register_cert'] = 'Fehler beim Speichern in der Blockchain';
$string['examination_end'] = 'Prüfungsende';
$string['examination_place'] = 'Prüfungsort';
$string['examination_start'] = 'Prüfungsstart';
$string['examination_regulations'] = 'Prüfungsordnung';
$string['examination_regulations_url'] = 'Prüfungsordnung URL';
$string['examination_regulations_id'] = 'Prüfungsordnung Version';
$string['examination_regulations_date'] = 'Prüfungsordnung Datum';
$string['expertise'] = 'Kenntnisse';
$string['expiredate'] = 'Ablaufdatum';
$string['expireperiod'] = 'Ablaufzeitraum';
$string['generate_adr_from_pk'] = 'Blockchain Adresse und Private Key ermitteln';
$string['generate_pk'] = 'Link zum generieren des Private Key per E-Mail versenden';
$string['headerconfig'] = 'Aussteller / Zertifizierungsstelle';
$string['html'] = 'Zertifikat';
$string['image'] = 'Bild';
$string['institution_pk'] = 'Private Key der Zertifizierungsstelle';
$string['invalid'] = 'Das Zertifikat ist ungültig';
$string['invalid_format'] = 'Ungültiges Dateiformat';

$string['issuer'] = 'Zertifizierungsstelle';
$string['issueraddress'] = 'Blockchainadresse der Zertifizierungsstelle';
$string['issuername_label'] = 'Name';
$string['issuername_descr'] = 'Name der Zertifizierungsstelle';

$string['issuerpob'] = 'Pob';
$string['issuerstreet'] = 'Straße';
$string['issuerzip'] = 'PLZ';
$string['issuerlocation'] = 'Ort';
$string['issuerurl'] = 'URL';
$string['issueremail'] = 'E-Mail';
$string['issuerdescription'] = 'Beschreibung';

$string['json'] = 'Metadaten';
$string['subject_new_certificate'] = 'Neues digitales Zertifikat';
$string['subject_new_digital_certificate'] = 'Neues digitales Zertifikat in der Blockchain';
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
$string['new_certifier_message'] = 'Hallo {$a->fullname} 
 
Sie wurden als neuer Zertifizierer für die Zertifizierungsstelle "{$a->institution}" auf {$a->url} hinzugefügt. 
 
Viele Grüße 
{$a->from}';
$string['new_certifier_message_html'] = '<p>Hallo {$a->fullname}</p>
<p>&nbsp;</p>
<p>Sie wurden als neuer Zertifizierer für die Zertifizierungsstelle "{$a->institution}" auf {$a->url} hinzugefügt.</p>
<p>&nbsp;</p>
<p>Viele Grüße</p>
<p>{$a->from}</p>';
$string['new_certifier_message_pk'] = 'Hallo {$a->fullname}

Sie wurden als neuer Zertifizierer für die Zertifizierungsstelle "{$a->institution}" auf {$a->url} hinzugefügt.

Um Zertifikate in der Blockchain registrieren und zu signieren, verwenden Sie bitte folgenden Private Key:
{$a->pk}

Bewahren Sie Ihren Private Key sicher auf und geben Sie ihn nicht weiter! Weitere Informationen zu diesem Thema finden Sie hier: https://www.oncampus.de/private_key

Viele Grüße
{$a->from}';
$string['new_certifier_message_pk_html'] = '<p>Hallo {$a->fullname}</p>
<p>&nbsp;</p>
<p>Sie wurden als neuer Zertifizierer für die Zertifizierungsstelle "{$a->institution}" auf {$a->url} hinzugefügt.</p>
<p>&nbsp;</p>
<p>Um Zertifikate in der Blockchain registrieren und zu signieren, verwenden Sie bitte folgenden Private Key:</p>
<p>{$a->pk}</p>
<p>&nbsp;</p>
<p>Bewahren Sie Ihren Private Key sicher auf und geben Sie ihn nicht weiter! Weitere Informationen zu diesem Thema finden Sie hier: https://www.oncampus.de/private_key</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>Viele Grüße</p>
<p>{$a->from}</p>';
$string['new_certifier_subject'] = 'Sie wurden als Zertifizierer hinzugefügt';
$string['new_pk_generated'] = '<p>Herzlichen Glückwunsch!</p>
<p>Für Sie wurde ein neuer Private Key erstellt.</p>
<p>Ihr Private Key: {$a->pk}</p>
<p>Behandeln Sie Ihren Private Key wie ein wichtiges Passwort und geben Sie ihn nicht weiter.</p>
<p>Mit Ihrem Private Key registrieren und signieren Sie Digitale Zertifikate in der Blockchain.</p>
<p>Schließen Sie diese Seite nicht und laden Sie sie nicht neu, bevor Sie Ihren Private Key gesichert haben!</p>';
$string['No_institution_found_in_IPFS'] = 'Es konnten keine Informationen zur Zertifizierungsstelle im IPFS gefunden werden';
$string['no_pref_found'] = 'Nutzereigenschaft "mod_ilddigitalcert_certifier" nicht gefunden für {$a->fullname}. 
Kontrollieren Sie bitte ob Sie korrekt angemeldet sind.';
$string['not_installed_correctly'] = 'Das Plugin "Digitale Zertifikate" wurde nicht vollständig installiert. 
Lesen Sie die Datei README.md oder wenden Sie sich an den Moodle Administrator';
$string['only_blockchain'] = 'Nur Zertifikate, die in der Blockchain registriert sind';
$string['only_nonblockchain'] = 'Nur Zertifikate, die nicht in der Blockchain registriert sind';
$string['overview'] = 'Übersicht';
$string['overview_intro'] = 'Hier sehen Sie eine Übersicht über alle erworbenen Zertifikate aus allen Kursen, in die Sie eingeschrieben sind oder waren.';
$string['pdf'] = 'PDF';
$string['preview'] = 'Vorschau für das Zertifikat';
$string['recipient'] = 'Empfänger';
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
$string['startdate'] = 'Ausstellungsdatum';
$string['subject_generate_pk'] = 'Generieren Sie Ihren Private Key als Zertifizierer';
$string['template'] = 'HTML Template';
$string['title'] = 'Zertifikat';
$string['toblockchain'] = 'In Blockchain registrieren und signieren';
$string['to_many_enrolments'] = 'Das Zertifikat kann aufgrund mehrerer paralleler Einschreibungen in diesen Kurs nicht ausgestellt werden.';
$string['unknown'] = 'Das Zertifikat ist in der Blockchain unbekannt';
$string['upload'] = 'Zertifikat hier hochladen';
$string['upload_again'] = 'Neues Zertifikat hochladen';
$string['use_address'] = 'Bereits vorhandene Blockchain Adresse eines Zertifizierers verwenden';
$string['valid'] = 'Das Zertifikat ist gültig';
$string['validation'] = 'Gültig';
$string['verify'] = 'Zertifikate überprüfen';
$string['verify_authenticity'] = 'Echtheit überprüfen';
/* $string['verify_authenticity_descr'] = 'In die PDF Version dieses Zertifikates ist eine Datei im JSON-Format eingebettet. 
Deren Hashwert ist in der Zertifikats-Blockchain gespeichert. So lässt sich jederzeit die Echtheit 
des Zertifikats überprüfen. Laden Sie dazu die PDF-Datei unter der 
URL <a href="{$a->url}">{$a->url}</a> hoch. 
<br />Ihre ausgedruckte Version des Zertifikates können Sie überprüfen indem Sie nebenstehenden QR-Code einscannen.
<br />HASH: <a href="{$a->url}?hash={$a->hash}">{$a->hash}</a>'; */
$string['verify_authenticity_descr'] = 'Um die Echtheit des Zertifikates zu überprüfen, laden Sie die PDF-Datei unter 
<a href="{$a->url}">{$a->url}</a> hoch. 
<br />Ihre ausgedruckte Version des Zertifikates können Sie überprüfen indem Sie nebenstehenden QR-Code einscannen.';
$string['verify_description'] = 'Überprüfen Sie hier Ihre digitalen Zertifikate in der Blockchain';
$string['waiting_for_pk_generation'] = 'Warte auf Erstellung eines Private Key durch Nutzer/in';
$string['waiting_for_registration'] = 'Warte auf Registrierung in der Blockchain';
