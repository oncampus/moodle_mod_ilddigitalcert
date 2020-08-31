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
 * Upgrade the database when upgrading the plugin.
 *
 * @package    mod_ilddigitalcert
 * @copyright  2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_ilddigitalcert_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2018110800) {

        // Define field expireperiod to be added to ilddigitalcert.
        $table = new xmldb_table('ilddigitalcert');

        $field = new xmldb_field('expiredate', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field expiredate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('expireperiod', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'expiredate');

        // Conditionally launch add field expireperiod.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ilddigitalcert savepoint reached.
        upgrade_mod_savepoint(true, 2018110800, 'ilddigitalcert');
    }

    if ($oldversion < 2018110801) {

        // Define field criteria to be added to ilddigitalcert.
        $table = new xmldb_table('ilddigitalcert');

        $field = new xmldb_field('criteria', XMLDB_TYPE_TEXT, null, null, null, null, null, 'expireperiod');

        // Conditionally launch add field criteria.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('expertise', XMLDB_TYPE_TEXT, null, null, null, null, null, 'criteria');

        // Conditionally launch add field expertise.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('examination_start', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'expertise');

        // Conditionally launch add field examination_start.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('examination_end', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'examination_start');

        // Conditionally launch add field examination_end.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('examination_place', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'examination_end');

        // Conditionally launch add field examination_place.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('examination_regulations', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'examination_place');

        // Conditionally launch add field examination_regulations.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('examination_regulations_url',
                                 XMLDB_TYPE_CHAR,
                                 '255',
                                 null, null, null, null,
                                 'examination_regulations');

        // Conditionally launch add field examination_regulations_url.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('examination_regulations_id',
                                XMLDB_TYPE_CHAR,
                                '100',
                                null, null, null, null,
                                'examination_regulations_url');

        // Conditionally launch add field examination_regulations_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('examination_regulations_date',
                                 XMLDB_TYPE_INTEGER,
                                 '10',
                                 null, null, null, null,
                                 'examination_regulations_id');

        // Conditionally launch add field examination_regulations_date.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ilddigitalcert savepoint reached.
        upgrade_mod_savepoint(true, 2018110801, 'ilddigitalcert');
    }

    if ($oldversion < 2018110900) {

        // Define table ilddigitalcert_issuer to be created.
        $table = new xmldb_table('ilddigitalcert_issuer');

        // Adding fields to table ilddigitalcert_issuer.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('location', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('zip', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('street', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pob', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('url', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('issuerid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table ilddigitalcert_issuer.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for ilddigitalcert_issuer.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ilddigitalcert savepoint reached.
        upgrade_mod_savepoint(true, 2018110900, 'ilddigitalcert');
    }

    if ($oldversion < 2018111000) {

        // Define table ilddigitalcert_issued to be created.
        $table = new xmldb_table('ilddigitalcert_issued');

        // Adding fields to table ilddigitalcert_issued.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('metadata', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table ilddigitalcert_issued.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for ilddigitalcert_issued.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ilddigitalcert savepoint reached.
        upgrade_mod_savepoint(true, 2018111000, 'ilddigitalcert');
    }

    if ($oldversion < 2018111001) {

        // Define field cmid to be added to ilddigitalcert_issued.
        $table = new xmldb_table('ilddigitalcert_issued');
        $field = new xmldb_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'timecreated');

        // Conditionally launch add field cmid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ilddigitalcert savepoint reached.
        upgrade_mod_savepoint(true, 2018111001, 'ilddigitalcert');
    }

    if ($oldversion < 2018111500) {

        // Define field issuer to be added to ilddigitalcert.
        $table = new xmldb_table('ilddigitalcert');
        $field = new xmldb_field('issuer', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1', 'description');

        // Conditionally launch add field issuer.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ilddigitalcert savepoint reached.
        upgrade_mod_savepoint(true, 2018111500, 'ilddigitalcert');
    }

    if ($oldversion < 2018111501) {

        // Define field template to be added to ilddigitalcert.
        $table = new xmldb_table('ilddigitalcert');
        $field = new xmldb_field('template', XMLDB_TYPE_TEXT, null, null, null, null, null, 'issuer');

        // Conditionally launch add field template.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ilddigitalcert savepoint reached.
        upgrade_mod_savepoint(true, 2018111501, 'ilddigitalcert');
    }

    if ($oldversion < 2018111600) {

        // Define field courseid to be added to ilddigitalcert_issued.
        $table = new xmldb_table('ilddigitalcert_issued');

        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'cmid');

        // Conditionally launch add field courseid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('inblockchain', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'courseid');

        // Conditionally launch add field inblockchain.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ilddigitalcert savepoint reached.
        upgrade_mod_savepoint(true, 2018111600, 'ilddigitalcert');
    }

    if ($oldversion < 2018111601) {

        // Define field name to be added to ilddigitalcert_issued.
        $table = new xmldb_table('ilddigitalcert_issued');
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'courseid');

        // Conditionally launch add field name.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ilddigitalcert savepoint reached.
        upgrade_mod_savepoint(true, 2018111601, 'ilddigitalcert');
    }

    if ($oldversion < 2018112000) {

        // Define field timemodified to be added to ilddigitalcert_issued.
        $table = new xmldb_table('ilddigitalcert_issued');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecreated');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ilddigitalcert savepoint reached.
        upgrade_mod_savepoint(true, 2018112000, 'ilddigitalcert');
    }

    if ($oldversion < 2018112200) {

        // Define field certhash to be added to ilddigitalcert_issued.
        $table = new xmldb_table('ilddigitalcert_issued');
        $field = new xmldb_field('certhash', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'timemodified');

        // Conditionally launch add field certhash.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('txhash', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'certhash');

        // Conditionally launch add field txhash.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ilddigitalcert savepoint reached.
        upgrade_mod_savepoint(true, 2018112200, 'ilddigitalcert');
    }

    if ($oldversion < 2019052000) {

        // Define field enrolmentid to be added to ilddigitalcert_issued.
        $table = new xmldb_table('ilddigitalcert_issued');
        $field = new xmldb_field('enrolmentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'txhash');

        // Conditionally launch add field enrolmentid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ilddigitalcert savepoint reached.
        upgrade_mod_savepoint(true, 2019052000, 'ilddigitalcert');
    }

    if ($oldversion < 2019052200) {

        // Define field address to be added to ilddigitalcert_issuer.
        $table = new xmldb_table('ilddigitalcert_issuer');
        $field = new xmldb_field('address', XMLDB_TYPE_CHAR, '42', null, null, null, null, 'issuerid');

        // Conditionally launch add field address.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ilddigitalcert savepoint reached.
        upgrade_mod_savepoint(true, 2019052200, 'ilddigitalcert');
    }

    if ($oldversion < 2019101800) {

        // Define field institution_token to be added to ilddigitalcert_issued.
        $table = new xmldb_table('ilddigitalcert_issued');
        $field = new xmldb_field('institution_token', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'txhash');

        // Conditionally launch add field institution_token.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Ilddigitalcert savepoint reached.
        upgrade_mod_savepoint(true, 2019101800, 'ilddigitalcert');
    }

    return true;
}