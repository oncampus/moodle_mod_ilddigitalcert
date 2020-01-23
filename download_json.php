<?php

require_once('../../config.php');
require_once('locallib.php');

$modulecontextid = optional_param('id', 0, PARAM_INT);
$uid = optional_param('uid', 0, PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);

if ($modulecontextid != 0 and $uid != 0) {
	download_json($modulecontextid, $uid);
}
else {
	redirect($CFG->wwwroot.'/mod/ilddigitalcert/view.php?id='.$cmid);
}
