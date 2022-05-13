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

$string['modulename'] = 'Digitales Zertifikat';
$string['modulenameplural'] = 'Digitale Zertifikate';
$string['pluginname'] = 'Digitales Zertifikat';
$string['pluginadministration'] = 'Plugin Administration';

$string['ilddigitalcertname'] = 'Name';

$string['add_certifier'] = 'Zertifizierer hinzufügen';
$string['automation'] = 'Automatisierung';
$string['automation_help'] = 'Wenn die Automatisierung aktiviert ist, werden die Zertifikate automatisch von dem angegebenen Zertifizierer signiert und in die Blockchain geschrieben. ';
$string['automation_report:contexturlname'] = 'Verwaltung der signierten Zertifikate';
$string['automation_report:enable'] = 'Wöchentlicher Bericht';
$string['automation_report:enable_help'] = 'Wenn diese Option aktiviert ist, erhält der ausgewählte Zertifizierer am Ende jeder Woche einen Bericht, der alle Zertifikate auflistet, die in der vorangegangen Woche im dessen oder deren Namen signiert wurden.';
$string['automation_report:end'] = '';
$string['automation_report:intro'] = '<p>Hi {$a},</p>
<p>folgende Zertifikate wurden kürzlich automatisch in ihrem Namen signiert und erfolgreich in die Blockchain geschrieben:</p>';
$string['automation_report:other_certs'] = 'Other certificates';
$string['automation_report:subject'] = 'Automatisch signierte Zertifikate';

$string['auto_certifier'] = 'Zertifizierer';
$string['auto_certifier_help'] = 'Geben sie hier einen Zertifizierer an, in dessen/dessem Namen die Zertifikate automatisiert signiert werden sollen. Geeignete Zertifizierer müssen in diesem Kurs eingeschrieben sein und bereits eine gültige Blockchain-Adresse haben.';
$string['auto_pk'] = 'Privater Schlüssel des Zertifizierers';
$string['auto_pk_help'] = 'Geben Sie hier den privaten Schlüssel des oben angegebenen Zertifizierers an.';
$string['block_heading'] = 'Digitale Zertifikate in der Blockchain';
$string['block_summary'] = '<p>Überprüfe hier die Echtheit Deiner digitalen Zertifikate.</p>
<p>Ziehe dazu einfach Dein Zertifikat (PDF, XML oder BCRT-Datei) per Drag and Drop in das Feld.</p>
<p>Deine ausgedruckte Version des Zertifikates kannst Du überprüfen indem Du
den untenstehenden QR-Code einscannst.
</p>';
$string['body_new_attribute'] = 'Mit dieser Nachricht erhalten Sie ein neues Attribut für die Vervollständigung Ihrer persönlichen Daten.';
$string['cert_file_description'] = 'PDF Zertifikat mit eingebetteten Metadaten.';
$string['cert_waiting_for_registration'] = 'Dieses Zertifikat wartet auf Registrierung und Signierung in der Blockchain durch berechtigte Zertifizierer.';
$string['certhash'] = 'Zertifikat-Hash';
$string['certificate'] = 'Zertifikat';
$string['certifier'] = 'Zertifizierer';
$string['certifier_address'] = 'Blockchain-Adresse des Zertifizierers';
$string['choose'] = 'Bitte wählen';
$string['configlabel_blockchain_url'] = 'Blockchain URL';
$string['configlabel_CertMgmt_address'] = 'Smart-Contract-Adresse CertMgmt';
$string['configlabel_custom_menu_entry'] = 'Eintrag im Nutzermenü';
$string['configlabel_failover_url'] = 'Alternative Blockchain URL';
$string['configlabel_IdentityMgmt_address'] = 'Smart-Contract-Adresse IdentityMgmt';
$string['configlabel_max_token_age'] = 'Maximales Tokenalter';
$string['configlabel_demo_mode'] = 'Demo Modus aktivieren';
$string['configdesc_blockchain_url'] = 'Diese Adresse wird benötigt um die Verbindung zu einem geeigneten Blockchain-Knoten herzustellen.';
$string['configdesc_CertMgmt_address'] = 'Dies ist die Adresse des Smart Contracts für die Verwaltung der Zertifikate in der Blockchain.<br/>
Adresse für den aktuellen (28.09.2020) Contract: <span style="color:green;">0x8a7e3622D3f200aBb1B00D25126f86256c7368dB</span>';
$string['configdesc_custom_menu_entry'] = 'Fügt einen Eintrag im Nutzermenü hinzu, der zur persönlichen Zertifikatsübersicht führt.';
$string['configdesc_failover_url'] = 'Diese URL wird verwendet, wenn die primäre Blockchain URL nicht erreichbar ist.';
$string['configdesc_IdentityMgmt_address'] = 'Dies ist die Adresse des Smart Contracts für die Verwaltung der Benutzerrollen in der Blockchain.<br/>
Adresse für den aktuellen (28.09.2020) Contract: <span style="color:green;">0xF40ec6b07009de471F3E2773b276F434F2c1c567</span>';
$string['configdesc_max_token_age'] = 'Diese Dauer gibt an nach welcher Zeit der Link zum Generieren eines Privat Keys seine Gültigkeit verliert.';
$string['configdesc_demo_mode'] = 'Zum Ausprobieren und Testen des Plugins können Sie diese Option aktivieren. Zertifikate werden dann auf eine seperate Demo Blockchain geschrireben, die sich nicht für produktive Zwecke eignet. Bitte verwenden Sie keine keine persöhnlichen oder andere zu schützende Daten, währende dieser Modus aktiviert ist.';
$string['criteria'] = 'Kriterien';
$string['data'] = 'Zertifikatsdaten';
$string['dcattributes'] = 'Digital Campus Attribute';
$string['dcbirdid'] = 'BIRD ID';
$string['dcxapikey'] = 'API Key';
$string['dcconnectorid'] = 'Connector ID';
$string['dcconnectorsettings'] = 'Connector-Einstellungen';
$string['dcconnector_pdfuploaddesc'] = 'PDF Zertifikatsdatei mit eingebetteten Zertifikats-Metadaten.';
$string['dchost'] = 'Domain';
$string['delete_certifier'] = 'Aus Blockchain entfernen';
$string['descconfig'] = 'Angaben zur Zertifizierungsstelle';
$string['description'] = 'Beschreibung';
$string['drag_n_drop'] = 'Hier klicken oder Dateien in dieses Feld ziehen (Drag & Drop) um sie hochzuladen';
$string['edci'] = 'EDCI';
$string['edit_certifiers'] = 'Zertifizierer verwalten';
$string['edit_issuers'] = 'Aussteller verwalten';
$string['error_choose'] = 'Wählen Sie eine Zertifizierungsstelle';
$string['error_choose_certifier'] = 'Wählen Sie eine:n Zertizierer:in';
$string['error_register_cert'] = 'Fehler beim Speichern in der Blockchain';
$string['error_revoke_cert'] = 'Fehler beim Wiederrufen';
$string['error_novalidblockchainurl'] = 'Es konnte keine valide oder aktive Blockchain-Url gefunden werden. Bitte überprüfen Sie die Plugin-Einstellungen und geben eine gültige Blockchain-Url an.';
$string['eventcertificateissued'] = 'Zertifikat ausgestellt';
$string['eventcertificateregistered'] = 'Zertifikat registriert';
$string['eventcertificatereissued'] = 'Zertifikat neuausgestellt';
$string['eventcertificaterevoked'] = 'Zertifikat wiederrufen';
$string['eventverificationcompleted'] = 'Verifikation abgeschlossen';
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
$string['extractmetadata'] = 'Metadaten extrahieren';
$string['file_upload_error'] = 'Fehler beim senden der Datei';
$string['generate_adr_from_pk'] = 'Blockchain Adresse und Private Key ermitteln';
$string['generatehash'] = 'Hash generieren';
$string['generate_pk'] = 'Link zum generieren des Private Key per E-Mail versenden';
$string['headerconfig'] = 'Aussteller / Zertifizierungsstelle';
$string['html'] = 'Zertifikat';
$string['image'] = 'Bild';
$string['institution_pk'] = 'Private Key der Zertifizierungsstelle';
$string['invalid'] = 'Das Zertifikat ist ungültig';
$string['invalid_format'] = 'Ungültiges Dateiformat';
$string['invalid_hash_format'] = 'Ungültiges Hash-Format';
$string['invalid_pk_format'] = 'Der gegebene Private Key ist ungültig.';

$string['issued'] = 'Das Zertifikat wurde ausgestellt, muss jedoch noch signiert und in der Blockchain registriert werden.';
$string['issuedcerts_report:contexturlname'] = 'Signiere die ausgestellten Zertifikate';
$string['issuedcerts_report:end'] = '';
$string['issuedcerts_report:intro'] = '<p>Hallo {$a},</p>
<p>folgende Zertifikate wurden kürzlich ausgestellt. Bitte signieren Sie diese über die folgenden Schaltflächen:</p>';
$string['issuedcerts_report:nocertifierincourse'] = 'Achtung, aktuell ist kein Zertifizierer in den Kurs {$a} eingeschrieben. Nur Zertifizierer können ausgestellte Zertifikate signieren und in der Blockchain registrieren. Bitte schreiben sie einen Zertifizierer in den Kurs ein. Für weitere Hilfe wenden Sie sich an einen Admin.';
$string['issuedcerts_report:other_certs'] = 'Weitere Zertifikate';
$string['issuedcerts_report:subject'] = 'Zertifikate benötigen Ihre Signatur';

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
$string['subject_new_attribute'] = 'Neues Attribut';
$string['subject_certificate_revoked'] = 'Ihr Zertifikat wurde wiederrufen';
$string['subject_new_certificate'] = 'Neues digitales Zertifikat';
$string['subject_new_digital_certificate'] = 'Neues digitales Zertifikat in der Blockchain';
$string['messageprovider:ilddigitalcert_issuedcerts_report'] = 'Kürzlich ausgestellte Zertifikate';
$string['messageprovider:ilddigitalcert_automation_report'] = 'Automatisch signierte Zertifikate';
$string['message_certificate_revoked'] = '<p>Hallo {$a->fullname},</p>
<p>Ihr digitales Zertifikat wurde wiederrufen.</p>
<p>Hier können Sie sich Ihr Zertifikat ansehen: <a href="{$a->url}">{$a->url}</a>.</p>
<p>Viele Grüße</p>
<p>Ihr {$a->from} Team</p>';
$string['message_new_certificate_html'] = '<p>Hallo {$a->fullname},</p>
<p>Sie haben ein digitales Zertifikat erhalten.</p>
<p>Hier können Sie sich Ihr Zertifikat ansehen: <a href="{$a->url}">{$a->url}</a>.</p>
<p>Viele Grüße</p>
<p>Ihr {$a->from} Team</p>';
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
$string['message_sendtowallet_subject'] = 'Ihr digitales Zertifikat';
$string['message_sendtowallet_body'] = 'Im Anhang dieser Nachricht erhalten Sie Ihr digitales Zertifikat zur weiteren Verwendung. Sie finden es außerdem im Ordner Dateien.';
$string['missingcertid'] = 'Zertifikat ID fehlt';
$string['msg_send_error'] = 'Fehler beim Senden der Nachricht';
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
$string['no_certifier'] = 'Keine geeigneten Zertifizierer gefunden';
$string['No_institution_found_in_IPFS'] = 'Es konnten keine Informationen zur Zertifizierungsstelle im IPFS gefunden werden';
$string['no_pref_found'] = 'Nutzereigenschaft "mod_ilddigitalcert_certifier" nicht gefunden für {$a->fullname}.
Kontrollieren Sie bitte ob Sie korrekt angemeldet sind.';
$string['not_installed_correctly'] = 'Das Plugin "Digitale Zertifikate" wurde nicht vollständig installiert.
Lesen Sie die Datei README.md oder wenden Sie sich an den Moodle Administrator';
$string['not_logged_in'] = 'Sie sind nicht im System angemeldet.';
$string['only_blockchain'] = 'Registrierte Zertifikate';
$string['only_nonblockchain'] = 'Unregistrierte Zertifikate';
$string['overview'] = 'Ihre erworbenen Zertifikate';
$string['overview_certifier'] = 'Übersicht ausgestellter Zertifikate';
$string['overview_course'] = 'Ausgestellte Zertifikate im Kurs "{$a}"';
$string['pdf'] = 'PDF';
$string['preview'] = 'Vorschau für das Zertifikat';
$string['recipient'] = 'Empfänger';
$string['reissue'] = 'Erneut ausstellen';
$string['reissue_confirmation'] = 'Wollen Sie folgende Zertifikate neuausstellen?';
$string['reissue_error_already_signed'] = '{$a} Zertifikat(e) konnten nicht neuausgestellt werden, da diese bereits signiert und in die Blockchain geschrieben wurden.';
$string['reissue_success'] = 'Das Zertifikat für: <b>{$a}</b> wurde erfolgreich neuausgestellt.';
$string['registered_and_signed'] = 'Das Zertifikat wurde erfolgreich signiert und in der Blockchain registriert.';
$string['revoke'] = 'Wiederrufen';
$string['revoked'] = 'Das Zertifikat wurde erfolgreich wiederrufen.';
$string['revoke_confirmation'] = 'Wollen Sie folgende Zertifikate wiederrufen?';
$string['revoke_error_invalid'] = '{$a} Zertifikate konnten nicht wiederrufen werden, da diese noch nicht in der Blockchain registriert wurden.';
$string['scan_qr_code'] = 'Um Ihr digitales Zertifikat an die Wallet zu senden, müssen Sie erst eine Verbindung zu dieser herstellen. Öffnen Sie dazu die App und scannen Sie den QR-Code. Folgen Sie anschließend den Anweisungen in der App.';
$string['select_user'] = 'Wählen Sie eine/n Nutzer/in aus!';
$string['send_automation_report'] = 'Erstattet Bericht über kürzlich automatisch signierte Zertifikate';
$string['send_issuedcerts_report'] = 'Erstattet Bericht über ausgestellte Zertifikate, die noch signiert werden müssen.';
$string['send_to_wallet'] = 'An Wallet senden';
$string['send_certificate_to_wallet'] = 'Senden Sie ihr digitales Zertifikat jetzt an Ihre Wallet. Anschließend können Sie es in der App auf dem Smartphone verwenden und mit anderen Institutionen teilen.';
$string['send_certificate_to_wallet_success'] = 'Das Zertifikat wurde an Ihre Wallet gesendet. Sie können es nun in der App auf Ihrem Smartphone verwenden.';
$string['settings_descconfig'] = '<p>Diese Parameter werden benötigt um eine Verbindung zur Blockchain aufzubauen und
um die Smart Contracts auszuführen, die verwendet werden um Zertifikate und Zertifizierer zu verwalten.</p>
<p><strong>Es können einfach die voreingestellten Default-Parameter verwendet werden um die DigiCerts Blockchain zu benutzen.</strong></p>
<p>Nähere Informationen finden Sie hier <a href="https://www.digicerts.de">DigiCerts</a>.</p>';
$string['settings_headerconfig'] = 'Blockchain Einstellungen';
$string['settings_headerconfig_general'] = 'Allgemeine Einstellungen';
$string['sign_add_certifier_with_pk'] = 'Hinzufügen eines neuen Zertifizierers mit Private Key signieren';
$string['sign_cert'] = 'Zertifikat in Blockchain registrieren und signieren';
$string['sign_confirmation'] = 'Wollen Sie folgende Zertifikate signieren und registrieren?';
$string['sign_delete_certifier_with_pk'] = 'Um den Zertifizierer aus der Blockchain zu entfernen, muss dieser Vorgang mit dem Private Key einer Zertifizierungsstelle signiert werden.';
$string['sign_error_already_signed'] = '{$a} Zertifikat(e) konnten nicht signiert werden, da diese bereits signiert und in die Blockchain geschrieben wurden.';
$string['sign_with_pk'] = 'Um ein Zertifikat in der Blockchain zu registrieren, muss dieser Vorgang mit dem dazugehörigen Private Key signiert werden.';
$string['startdate'] = 'Ausstellungsdatum';
$string['study_field'] = 'Studienfach von Interesse';
$string['subject_generate_pk'] = 'Generieren Sie Ihren Private Key als Zertifizierer';
$string['template'] = 'HTML Template';
$string['title'] = 'Zertifikat';
$string['toblockchain'] = 'Registrieren und signieren';
$string['to_many_enrolments'] = 'Das Zertifikat kann aufgrund mehrerer paralleler Einschreibungen in diesen Kurs nicht ausgestellt werden.';
$string['unknown'] = 'Das Zertifikat ist in der Blockchain unbekannt';
$string['upload'] = 'Zertifikat hier hochladen';
$string['upload_again'] = 'Neues Zertifikat hochladen';
$string['use_address'] = 'Bereits vorhandene Blockchain Adresse eines Zertifizierers verwenden';
$string['valid'] = 'Das Zertifikat ist gültig';
$string['validation'] = 'Gültig';
$string['validfrom'] = 'Gültig ab';
$string['validuntil'] = 'Gültig bis';
$string['verify'] = 'Zertifikat überprüfen';
$string['verify_authenticity'] = 'Gültigkeit prüfen';
$string['verify_authenticity_descr'] = 'Um die Gültigkeit des Zertifikates zu überprüfen, laden Sie die PDF-Datei unter
<a href="{$a->url}">{$a->url}</a> hoch.
<br />Ihre ausgedruckte Version des Zertifikates können Sie überprüfen indem Sie nebenstehenden QR-Code einscannen.';
$string['verify_description'] = 'Überprüfen Sie hier Ihre digitalen Zertifikate in der Blockchain';
$string['verify_hash'] = 'Hash prüfen';
$string['waiting_for_pk_generation'] = 'Warte auf Erstellung eines Private Key durch Nutzer/in';
$string['waiting_for_registration'] = 'Warte auf Registrierung in der Blockchain';
$string['waiting_for_request'] = 'Warte auf Anfrage';
$string['wrongcertidornotloggedin'] = 'Falsche Zertifikat ID';
$string['wrong_relationship'] = 'Die Verbindung Ihrer Wallet mit dem System ist fehlerhaft. Bitte wenden Sie sich an einen Administrator.';
