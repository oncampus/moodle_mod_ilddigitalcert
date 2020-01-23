<?php

class mod_ilddigitalcert_observer {

    public static function user_unenrolled(\core\event\user_enrolment_deleted $event) {
		global $CFG, $USER, $DB;

        // check if course contains instances of mod_ilddigitalcert
        $courseid = $event->courseid;

        $sql = "SELECT 0, COUNT(*) as 'count' 
                  FROM {modules} m, {course_modules} cm 
                 WHERE cm.module = m.id 
                   AND cm.course = :course 
                   AND m.name = 'ilddigitalcert' 
                   AND cm.deletioninprogress = 0 ";

        $params = array('course' => $courseid);

        $result = $DB->get_records_sql($sql, $params);

        /*
        Array
        (
            [0] => stdClass Object
                (
                    [0] => 0
                    [count] => 5
                )
        )
        */

        if ($result[0]->count > 0) {
            require_once($CFG->dirroot.'/mod/ilddigitalcert/locallib.php');
            reset_user($courseid, $event->relateduserid);
        }
		return;
    }
}