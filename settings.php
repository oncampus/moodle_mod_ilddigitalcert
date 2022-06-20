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
 * Plugin administration pages are defined here.
 *
 * @package     mod_ilddigitalcert
 * @category    admin
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $modfolder = new admin_category('modildilddigitalcertfolder',
                                    new lang_string('pluginname',
                                    'mod_ilddigitalcert'),
                                    $module->is_enabled() === false);
    $ADMIN->add('modsettings', $modfolder);
    // Add the Settings admin menu entry.

    $settings->visiblename = new lang_string('pluginname', 'mod_ilddigitalcert');

    $settings->add(new admin_setting_heading(
        'settings_headerconfig',
        get_string('settings_headerconfig', 'mod_ilddigitalcert'),
        get_string('settings_descconfig', 'mod_ilddigitalcert'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_ilddigitalcert/demo_mode',
        get_string('configlabel_demo_mode', 'mod_ilddigitalcert'),
        get_string('configdesc_demo_mode', 'mod_ilddigitalcert'),
        1)
    );
    $settings->add(new admin_setting_configtext(
        'ilddigitalcert/blockchain_url',
        get_string('configlabel_blockchain_url', 'mod_ilddigitalcert'),
        get_string('configdesc_blockchain_url', 'mod_ilddigitalcert'),
        'http://quorum.th-luebeck.de:8545'
    ));
    $settings->add(new admin_setting_configtext(
        'ilddigitalcert/failover_url',
        get_string('configlabel_failover_url', 'mod_ilddigitalcert'),
        get_string('configdesc_failover_url', 'mod_ilddigitalcert'),
        'http://quorum.th-luebeck.de:8545'
    ));


    $settings->add(new admin_setting_heading(
        'settings_headerconfig_general',
        get_string('settings_headerconfig_general', 'mod_ilddigitalcert'),
        '',
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'ilddigitalcert/custom_menu_entry',
        get_string('configlabel_custom_menu_entry', 'mod_ilddigitalcert'),
        get_string('configdesc_custom_menu_entry', 'mod_ilddigitalcert'),
        1)
    );

    // TODO: max token age.
    $settings->add(new admin_setting_configtext(
        'ilddigitalcert/max_token_age',
        get_string('configlabel_max_token_age', 'mod_ilddigitalcert'),
        get_string('configdesc_max_token_age', 'mod_ilddigitalcert'),
        7,
        PARAM_INT
    ));

    $ADMIN->add('modildilddigitalcertfolder', $settings);
    $ADMIN->add('modildilddigitalcertfolder',
                new admin_externalpage('ilddigitalcert_edit_issuers',
                                       get_string('edit_issuers', 'mod_ilddigitalcert'),
                                       $CFG->wwwroot . '/mod/ilddigitalcert/edit_issuers.php'));
    $ADMIN->add('modildilddigitalcertfolder',
                new admin_externalpage('ilddigitalcert_edit_certifiers',
                                       get_string('edit_certifiers', 'mod_ilddigitalcert'),
                                       $CFG->wwwroot . '/mod/ilddigitalcert/edit_certifiers.php'));
    $ADMIN->add('modildilddigitalcertfolder',
                new admin_externalpage('ilddigitalcert_dcconnectorsettings',
                                       get_string('dcconnectorsettings', 'mod_ilddigitalcert'),
                                       $CFG->wwwroot . '/mod/ilddigitalcert/dcconnectorsettings.php'));
}
// Prevent Moodle from adding settings block in standard location.
$settings = null;
