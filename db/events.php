<?php

$observers = array (
	array (
        'eventname' => '\core\event\user_enrolment_deleted',
        'callback' => 'mod_ilddigitalcert_observer::user_unenrolled',
    )
);

?>