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
 * Shows the verify site
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once('locallib.php');
require_once('web3lib.php');

require_login();

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/mod/ilddigitalcert/verify.php');
$PAGE->set_title(format_string(get_string('pluginname', 'mod_ilddigitalcert')));
$PAGE->set_heading(get_string('pluginname', 'mod_ilddigitalcert'));

echo $OUTPUT->header();
$url = get_config('ilddigitalcert', 'blockchain_url');

echo '<div style="background-color: rgba(16,111,111,.7);margin:-15px -20px 80px -30px;min-height: 235px;">
    <div style="float:left;margin:30px 15px -20px 30px;">
      <img style="width: 240px;float:left;" src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/blockchain.jpg">
    </div>
    <div style="padding:15px 6px;float: left;width: calc(100% - 350px);min-width: 300px;max-width: 700px;padding-left: 30px;">
      <h3 style="color: white;">Digitale Zertifikate in der Blockchain</h3>
      <b style="color: white;">
        <p>Überprüfe hier die Echtheit Deiner digitalen Zertifikate.</p>
        <p>Ziehe dazu einfach Dein Zertifikat (PDF oder BCRT-Datei) per Drag and Drop in das Feld.</p>
        <p>Deine ausgedruckte Version des Zertifikates kannst Du überprüfen indem Du
        den untenstehenden QR-Code einscannst.
        </p>
        <p></p>
      </b>
    </div>
    <div style="clear:both;height: 0px;"></div>
  </div>';

$imgurls = array($CFG->wwwroot.'/mod/ilddigitalcert/pix/botti_blockchain_check_1.png',
          $CFG->wwwroot.'/mod/ilddigitalcert/pix/botti_blockchain_check_2.png',
          $CFG->wwwroot.'/mod/ilddigitalcert/pix/botti_blockchain_check_3.gif',
          $CFG->wwwroot.'/mod/ilddigitalcert/pix/botti_blockchain_check_4.png',
          $CFG->wwwroot.'/mod/ilddigitalcert/pix/botti_blockchain_check_success.png',
          $CFG->wwwroot.'/mod/ilddigitalcert/pix/botti_blockchain_check_error.png');

echo '<p id="p_dropzone">';
echo '  <div id="dropzone" onclick="document.getElementById(\'asText\').click();"
          align="center" style="padding:20px;min-width:300px;width:100%;
          max-width: 800px;height:170px;margin:30px auto;border: 2px dashed #bfbfbf;">';
// Backup: display:-webkit-flex;display:flex;-webkit-align-items:center;align-items:center;" //!
echo '    <input type="file" id="asText" style="display: none;">';
echo '    <img src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/dnd_arrow.gif"/>';
echo '    <p>'.get_string('drag_n_drop', 'mod_ilddigitalcert').'</p>';
echo '  </div>';
echo '  <div id="list" style="display:none;padding:20px;width:300px;height:100px;margin:0px auto;"></div>';
echo '</p>';

echo '<p id="textbox" align="center"></p>';
echo '<p style="display:none"><img id="loader" src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/loader.gif"
  style="height: 10px;float: inherit;position: inherit;"/></p>';

echo '<div id="verifydiv" align="center">
    <div align="right" style="padding: 10px;float:left;width:50%;">
      <img id="botti_blockchain_check_1" width="150px" src="'.$imgurls[0].'" style="display:none;"/>
      <img id="botti_blockchain_check_2" width="150px" src="'.$imgurls[1].'" style="display:none;"/>
      <img id="botti_blockchain_check_3" width="150px" src="'.$imgurls[2].'" style="display:none;"/>
      <img id="botti_blockchain_check_4" width="150px" src="'.$imgurls[3].'" style="display:none;"/>
      <img id="botti_blockchain_check_5" width="150px" src="'.$imgurls[4].'" style="display:none;"/>
      <img id="botti_blockchain_check_6" width="150px" src="'.$imgurls[5].'" style="display:none;"/>
    </div>
    <div id="iconcheckdiv" style="display:none;">
      <img id="img-check-load" width="16px" style="margin-right:5px; vertical-align:top; float:left;clear:left;"
        src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/icon_check_load.gif'.'" /></div>
    <div id="iconerrordiv" style="display:none;">
      <img id="img-error" width="16px" style="margin-right: 5px;vertical-align:top; float:left;clear:left;"
        src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/icon_error.png'.'" />
    </div>
    <div id="iconerrordiv" style="display:none;">
      <img  id="img-check"width="16px" style="margin-right: 5px;vertical-align:top; float:left;clear:left;"
        src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/icon_check.png'.'" />
    </div>
    <div align="left" style="padding: 10px;float:left;width:50%;max-width: 250px;">
      <h3 id="h1" style="display:none;word-wrap: break-word;">Verifiziere</h3>
      <p id="p-hash" style="font-size: 10pt;word-wrap: break-word;display:none"></p>
      <p id="p1" style="display:none; margin:0px;clear: both;">
        <img id="img-check-load-1" width="16px" style="margin-right:5px; vertical-align:top; float:left;clear:left;"
          src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/icon_check_load.gif'.'" />
        Metadaten extrahieren
      </p>
      <p id="p2" style="display:none; margin:0px;clear: both;">
        <img id="img-check-load-2" width="16px" style="margin-right:5px; vertical-align:top; float:left;clear:left;"
          src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/icon_check_load.gif'.'" />
        Hash generieren
      </p>
      <p id="p3" style="display:none; margin:0px;clear: both;">
        <img id="img-check-load-3" width="16px" style="margin-right:5px; vertical-align:top; float:left;clear:left;"
          src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/icon_check_load.gif'.'" />
        Hash prüfen
      </p>
      <p id="p4" style="display:none; margin:0px;clear: both;">
        <img id="img-check-load-4" width="16px" style="margin-right:5px; vertical-align:top; float:left;clear:left;"
          src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/icon_check_load.gif'.'" />
        Gültigkeit prüfen
      </p>
      <p id="p5" style="display:none;color:#106F6F; margin:0px;clear: both;">
        <img id="img-check-1" width="16px" style="margin-right: 5px;vertical-align:top; float:left;clear:left;"
          src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/icon_check_all.png'.'" />
        <b>Gültig!</b>
      </p>
      <p id="p6" style="display:none;color:#B21E1E; margin:0px;clear: both;">
        <img id="img-error-1" width="16px" style="margin-right: 5px;vertical-align:top; float:left;clear:left;"
          src="'.$CFG->wwwroot.'/mod/ilddigitalcert/pix/icon_error.png'.'" />
        <b>Ungültig</b>
      </p>
    </div>
  </div>';

echo '<p id="assertionPage" style="clear: both;"></p>';

// TODO Use language files for text.
echo '<div id="certdata" align="center"
        style="display:none;padding:20px;min-width:300px;width:100%;max-width:650px;margin:30px auto;border: 0px solid #bfbfbf;">';
echo '<p align="left" style="margin: 0 auto;max-width: 610px;">
        Hash: <span id="span-result-hash" style="color:#106F6F;word-wrap: break-word;"></span><br/>';
echo 'Gültig ab <span id="span-result-start" style="color:#106F6F;"></span> ';
echo '<span id="prespan-result-end" style="display:none;">bis <span id="span-result-end" style="color:#106F6F;"></span></span>';
echo 'Zertifizierungsstelle: <br/><span id="span-result-institution"></span></p>';
echo '</div>';

// Backup: zoom: 0.75; -moz-transform: scale(0.75); -moz-transform-origin: 0 0; -o-transform: scale(0.75);//!
// Backup: -o-transform-origin: 0 0; -webkit-transform: scale(0.75); -webkit-transform-origin: 0 0;//!
// Backup: <iframe id="certpageiframe" style="display:none;border: 1px solid #bfbfbf;padding:15px; margin: 20px auto;//!
// Backup: width:100%;max-width: 800px;-webkit-transform: scale(1.00); -webkit-transform-origin: 0 0;"></iframe>;//!
echo '<iframe id="certpageiframe"
        style="display:none;border: 0px solid #bfbfbf;padding:15px;margin: 20px auto;width:100%;max-width: 800px;"></iframe>';
echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>'; // TODO
$PAGE->requires->js(new moodle_url($CFG->wwwroot.'/mod/ilddigitalcert/js/verify.js'));

echo $OUTPUT->footer();