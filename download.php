<?php
require_once('../../config.php');
require_once('locallib.php');
// TODO Berechtigungen prüfen
$modulecontextid = optional_param('id', 0, PARAM_INT);
$icid = optional_param('icid', 0, PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$download = optional_param('download', 'json', PARAM_RAW);
//die($download);
if ($modulecontextid != 0 and $icid != 0) {
	download_json($modulecontextid, $icid, $download);
	
}
else {
	redirect($CFG->wwwroot.'/mod/ilddigitalcert/view.php?id='.$cmid);
}
