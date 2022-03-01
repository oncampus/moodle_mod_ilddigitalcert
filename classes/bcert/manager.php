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

defined('MOODLE_INTERNAL') || die();

/**
 * Library of helper functions for converting certificates and navigate their data structures.
 *
 *
 * @package     mod_ilddigitalcert
 * @copyright   2021 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager
{
    /**
     * Checks if the object has a value for key $key, and returns the value if it exists.
     *
     * @param object $object Object
     * @param string $key Key
     * @return *
     */
    public static function get_if_object_key_exists($object, $key)
    {
        if (isset($object->{$key})) {
            return $object->$key;
        } else {
            return null;
        }
    }

    /**
     * Checks if the array has a value for key $key, and returns the value if it exists.
     *
     * @param array $array Array
     * @param string $key Key
     * @return *|null
     */
    public static function get_if_array_key_exists($array, $key)
    {
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
    public static function xml_escape($string)
    {
        return str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $string);
    }
}