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
 * Scheduled task definitions for the ilddigitalcert module
 *
 * @package    mod_ilddigitalcert
 * @copyright  2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'mod_ilddigitalcert\task\send_automation_report',
        'blocking' => 0,
        'minute' => 'R',
        'hour' => '12',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '5',
    ],
    [
        'classname' => 'mod_ilddigitalcert\task\send_issuedcerts_report',
        'blocking' => 0,
        'minute' => 'R',
        'hour' => '18',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
