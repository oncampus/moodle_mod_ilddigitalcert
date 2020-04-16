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
 * @copyright   2020 ILD TH Lübeck <support@oncampus.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
   // https://docs.moodle.org/dev/Admin_settings
   
   $modfolder = new admin_category('modildilddigitalcertfolder', new lang_string('pluginname', 'mod_ilddigitalcert'), $module->is_enabled() === false);
   $ADMIN->add('modsettings', $modfolder);
	// Add the Settings admin menu entry.
	
	$settings->visiblename = new lang_string('pluginname', 'mod_ilddigitalcert');
	
	$settings->add(new admin_setting_heading(            
		'settings_headerconfig',            
		get_string('settings_headerconfig', 'mod_ilddigitalcert'),            
		get_string('settings_descconfig', 'mod_ilddigitalcert'),''
	));

	// TODO Defaultwerte auf produktive quorum Blockchain ändern
	$settings->add(new admin_setting_configtext(            
		'ilddigitalcert/blockchain_url',            
		get_string('configlabel_blockchain_url', 'mod_ilddigitalcert'),            
		get_string('configdesc_blockchain_url', 'mod_ilddigitalcert'), 'http://certotrust-01.th-luebeck.de:57010'
	));
	$settings->add(new admin_setting_configtext(
		'ilddigitalcert/failover_url',
		get_string('configlabel_failover_url', 'mod_ilddigitalcert'),            
		get_string('configdesc_failover_url', 'mod_ilddigitalcert'), 'http://certotrust-01.th-luebeck.de:57010'
	));
	$settings->add(new admin_setting_configtext(            
		'ilddigitalcert/CertMgmt_address',            
		get_string('configlabel_CertMgmt_address', 'mod_ilddigitalcert'),            
		get_string('configdesc_CertMgmt_address', 'mod_ilddigitalcert'), '0x83351591391e960924f10Fa49C078dad63CEd6C0'
	));
	$settings->add(new admin_setting_configtext(            
		'ilddigitalcert/IdentityMgmt_address',            
		get_string('configlabel_IdentityMgmt_address', 'mod_ilddigitalcert'),            
		get_string('configdesc_IdentityMgmt_address', 'mod_ilddigitalcert'), '0xBf4Cc235a96A74C359Fb25773764516494a1a031'
	));
	
	$settings->add(new admin_setting_heading(            
		'settings_headerconfig_general',            
		get_string('settings_headerconfig_general', 'mod_ilddigitalcert'),            
		'',''
	));

	$settings->add(new admin_setting_configcheckbox(
		'ilddigitalcert/custom_menu_entry',
		get_string('configlabel_custom_menu_entry', 'mod_ilddigitalcert'),            
		get_string('configdesc_custom_menu_entry', 'mod_ilddigitalcert'),
		1)
	);
	
	// TODO: max token alter
	$settings->add(new admin_setting_configtext(            
		'ilddigitalcert/max_token_age',            
		get_string('configlabel_max_token_age', 'mod_ilddigitalcert'),            
		get_string('configdesc_max_token_age', 'mod_ilddigitalcert'), 
		7,
		PARAM_INT
	));

	$ADMIN->add('modildilddigitalcertfolder', $settings);
	$ADMIN->add('modildilddigitalcertfolder', new admin_externalpage('ilddigitalcert_edit_issuers', get_string('edit_issuers', 'mod_ilddigitalcert'), $CFG->wwwroot . '/mod/ilddigitalcert/edit_issuers.php'));
	$ADMIN->add('modildilddigitalcertfolder', new admin_externalpage('ilddigitalcert_edit_certifiers', get_string('edit_certifiers', 'mod_ilddigitalcert'), $CFG->wwwroot . '/mod/ilddigitalcert/edit_certifiers.php'));
	
}
// Prevent Moodle from adding settings block in standard location.
$settings = null;