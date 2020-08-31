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
 * Internal configuration file including all context urls for the
 * metadate schema.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $contexturl;

$contexturl = new stdClass();

$contexturl->openbadges = 'https://w3id.org/openbadges/v2';

$contexturl->institutionTokenILD = 'https://raw.githubusercontent.com/ild-thl/schema_extension_ild/master/institution_token_ild.json';

$contexturl->addressB4E = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/AddressB4E/context.json';
$contexturl->assertionpageB4E = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/AssertionPageB4E/context.json';
$contexturl->assertionreferenceB4E = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/AssertionReferenceB4E/context.json';
$contexturl->badgeInfoB4E = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/BadgeInfoB4E/context.json';
$contexturl->badgeexpertiseB4E = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/BadgeExpertiseB4E/context.json';
$contexturl->badgetemplateB4E = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/BadgeTemplateB4E/context.json';
$contexturl->contractB4E = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/ContractB4E/context.json';
$contexturl->examinationB4E = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/ExaminationB4E/context.json';
$contexturl->examinationRegulationsB4E = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/ExaminationRegulationsB4E/context.json';
$contexturl->recipientB4E = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/RecipientB4E/context.json';
$contexturl->signatureB4E = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/SignatureB4E/context.json';
$contexturl->verifyB4E = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/VerifyB4E/context.json';