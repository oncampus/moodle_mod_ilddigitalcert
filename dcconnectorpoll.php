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
 * Script that is polled to check for relationship requests
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once('dcconnectorlib.php');

require_login();

$result = new stdClass();

if (isguestuser()) {
    $result->status = get_string('not_logged_in', 'mod_ilddigitalcert');
    echo json_encode($result);
    exit;
}

$host = get_config('mod_ilddigitalcert', 'dchost');
$xapikey = get_config('mod_ilddigitalcert', 'dcxapikey');
$apiresult = callAPI('GET', $host.'/api/v1/RelationshipRequests/OpenIncoming', false, $xapikey);
$apiresult = json_decode($apiresult);
$templateid = get_user_preferences('mod_ilddigitalcert_template_id', 'error', $USER->id);
foreach ($apiresult->result as $ar) {
    if ($templateid == $ar->relationshipTemplateId) {
        if (checkrequest($ar)) {
            // Accept request.
            $data = '{"content": {}}';
            $acceptresult = callAPI('PUT', $host.'/api/v1/RelationshipRequests/'.$ar->id.'/Accept', $data, $xapikey);
            $accept = json_decode($acceptresult);
            if (isset($accept->result->relationshipId)) {
                set_user_preference('mod_ilddigitalcert_relationship_id', $accept->result->relationshipId, $USER->id);
                unset_user_preference('mod_ilddigitalcert_template_id', $USER->id);
                // Get relationship.
                $relresult = callAPI('GET', $host.'/api/v1/Relationships/'.$accept->result->relationshipId, false, $xapikey);
                $relresult = json_decode($relresult);
                if (isset($relresult->result->from)) {
                    // Save wallet id in userpref.
                    set_user_preference('mod_ilddigitalcert_wallet_id', $relresult->result->from, $USER->id);
                }
                $result->status = 'request_accepted';
            }
        } else {
            $result->status = 'bad_request';
        }
        exit(json_encode($result));
    }
}
$result->status = 'polling';
echo json_encode($result);
