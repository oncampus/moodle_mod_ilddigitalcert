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
 * Observer for resetting course progress when users are unenrolled.
 *
 * @package    mod_ilddigitalcert
 * @copyright  2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_ilddigitalcert_observer {

    /**
     * Resets the course progress of a user in a course when they unenrol.
     *
     * @param \core\event\user_enrolment_deleted $event
     * @return void
     */
    public static function user_unenrolled(\core\event\user_enrolment_deleted $event) {
        global $CFG, $DB;

        // Check if course contains instances of mod_ilddigitalcert.
        $courseid = $event->courseid;

        $sql = "SELECT 0, COUNT(*) as 'count'
                FROM {modules} m, {course_modules} cm
                WHERE cm.module = m.id
                   AND cm.course = :course
                   AND m.name = 'ilddigitalcert'
                   AND cm.deletioninprogress = 0 ";

        $params = array('course' => $courseid);

        $result = $DB->get_records_sql($sql, $params);
        // Reset only if the course contains an active ilddigitalcert activity.
        if ($result[0]->count > 0) {
            require_once($CFG->dirroot.'/mod/ilddigitalcert/locallib.php');
            // Reset course progress for the unenroled user.
            reset_user($courseid, $event->relateduserid);
        }
        return;
    }
}
