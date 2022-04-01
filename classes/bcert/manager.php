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

namespace mod_ilddigitalcert\bcert;

/**
 * Library of helper functions for converting certificates and navigate their data structures.
 *
 *
 * @package     mod_ilddigitalcert
 * @copyright   2021 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    const CONTEXT_OPENBADGES = 'https://w3id.org/openbadges/v2';
    const CONTEXT_ILD_INSTITUTION_TOKEN = 'https://raw.githubusercontent.com/ild-thl/schema_extension_ild/master/institution_token_ild.json';
    const CONTEXT_B4E_ADDRESS = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/AddressB4E/context.json';
    const CONTEXT_B4E_ASSERTIONPAGE = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/AssertionPageB4E/context.json';
    const CONTEXT_B4E_ASSERTIONREFERENCE = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/AssertionReferenceB4E/context.json';
    const CONTEXT_B4E_BADGEINFO = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/BadgeInfoB4E/context.json';
    const CONTEXT_B4E_BADGEEXPERTISE = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/BadgeExpertiseB4E/context.json';
    const CONTEXT_B4E_BADGETEMPLATE = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/BadgeTemplateB4E/context.json';
    const CONTEXT_B4E_CONTRACT = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/ContractB4E/context.json';
    const CONTEXT_B4E_EXAMINATION = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/ExaminationB4E/context.json';
    const CONTEXT_B4E_EXAMINATION_REGULATIONS = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/ExaminationRegulationsB4E/context.json';
    const CONTEXT_B4E_RECIPIENT = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/RecipientB4E/context.json';
    const CONTEXT_B4E_SIGNATURE = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/SignatureB4E/context.json';
    const CONTEXT_B4E_VERIFY = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/VerifyB4E/context.json';

    /**
     * Checks if the object has a value for key $key, and returns the value if it exists.
     *
     * @param \stdClass $haystack Object
     * @param string $key Key
     * @return mixed
     */
    public static function get_if_key_exists($haystack, $key) {
        if (is_array($haystack) && array_key_exists($key, $haystack)) {
            return $haystack[$key];
        }
        if (isset($haystack->{$key})) {
            return $haystack->$key;
        }

        return null;
    }

    /**
     * Checks if the array has a value for key $key, and returns the value if it exists.
     *
     * @param array $array Array
     * @param string $key Key
     * @return mixed|null
     */
    public static function get_if_array_key_exists($array, $key) {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        } else {
            return null;
        }
    }

    /**
     * Escapes all characters that have special meaning in xml.
     *
     * @param string $string XML String
     * @return string
     */
    public static function xml_escape($string) {
        return str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $string);
    }

    /**
     * Get logo/image file of an issuer.
     *
     * @param int $issuerid
     * @return stored_file|null
     */
    public static function get_issuer_image($issuerid) {
        $context = \context_system::instance();

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_ilddigitalcert', 'issuer', $issuerid);
        foreach ($files as $file) {
            if ($file->get_filename() != '.') {
                return $file;
            }
        }

        return null;
    }

    /**
     * Get image file of a certificate.
     *
     * @param int $cmid Context module id.
     * @return stored_file|null
     */
    public static function get_certificate_image($cmid) {
        $context = \context_module::instance($cmid);
        // Get image file.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_ilddigitalcert', 'content', 0);
        foreach ($files as $file) {
            if ($file->get_filename() != '.') {
                return $file;
            }
        }

        return null;
    }

    /**
     * Get a string conatining the roles of a user in a given course.
     *
     * @param int $userid
     * @param int $courseid
     * @return string
     */
    public static function get_role_in_course($userid, $courseid) {
        $coursecontext = \context_course::instance($courseid);
        $roles = get_user_roles($coursecontext, $userid, true);

        return array_reduce(
            $roles,
            function($ax, $dx) {
                if (empty($ax)) {
                    return $dx->shortname;
                }
                return $ax . ", {$dx->shortname}";
            },
            ''
        );
    }
}
