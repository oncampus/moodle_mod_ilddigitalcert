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
 * Library of interface functions and constants.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns true if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true|null True if the feature is supported, null otherwise.
 */
function ilddigitalcert_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_ilddigitalcert into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_ilddigitalcert_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function ilddigitalcert_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('ilddigitalcert', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_ilddigitalcert in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_ilddigitalcert_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function ilddigitalcert_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('ilddigitalcert', $moduleinstance);
}

/**
 * Removes an instance of the mod_ilddigitalcert from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function ilddigitalcert_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('ilddigitalcert', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('ilddigitalcert', array('id' => $id));

    return true;
}

/**
 * Triggers the issuance of a certificate for the current user when the activity becomes available to them.
 *
 * @param cm_info $cm
 * @return void
 */
function ilddigitalcert_cm_info_dynamic(cm_info $cm) {
    global $USER, $CFG, $DB;
    // Try to get $cm->get_user_visible() wich might throw errors in moodle versions prior to 3.9.10.
    // In case of errors default $uservisible to true.

    try {
        $uservisible = $cm->get_user_visible();
    } catch (\Exception $e) {
        $uservisible = true;
    }

    // User can access the activity.
    if ($uservisible) {
        if ($cm->available && !empty($cm->availability)) {
            $courseid = $cm->get_course()->id;
            $coursecontext = context_course::instance($courseid);
            if (!is_enrolled($coursecontext, $USER)) {
                return;
            }
            // Get enrolmentid.
            $sql = 'SELECT ue.id FROM {user_enrolments} ue, {enrol} e
                    WHERE ue.enrolid = e.id
                    and e.courseid = :courseid
                    and ue.userid = :userid ';
            $params = array('courseid' => $courseid, 'userid' => $USER->id);
            if ($enrolment = $DB->get_records_sql($sql, $params)) {
                // Certificate will not be issued, if user is enrolled with more than 1 enrolments.
                if (count($enrolment) > 1) {
                    \core\notification::error(get_string('to_many_enrolments', 'mod_ilddigitalcert'));
                    return;
                }
            }
            // Issue certificate.
            require_once($CFG->dirroot . '/mod/ilddigitalcert/locallib.php');
            $metacertificate = \mod_ilddigitalcert\bcert\certificate::new($cm, $USER);
            issue_certificate($metacertificate, $cm);
        }
    }
}
