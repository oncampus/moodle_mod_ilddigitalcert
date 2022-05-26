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

namespace mod_ilddigitalcert;

defined('MOODLE_INTERNAL') || die();

use mod_ilddigitalcert\bcert\certificate;

require_once(__DIR__ . './../locallib.php');

/**
 * Tests for the creation of certificates and converting them between
 * edci and openBadge standards.
 *
 *
 * @package     mod_ilddigitalcert
 * @copyright   2022 Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bcert_test extends \advanced_testcase {
    public function test_create_cert() {
        $this->resetAfterTest(true);
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $course = $this->getDataGenerator()->create_course();
        $issuer = $this->create_issuer();
        $ilddigitalcert = $this->getDataGenerator()->create_module('ilddigitalcert', array('course' => $course->id, 'issuer' => $issuer->id));
        $cm = $DB->get_record('course_modules', array('course' => $course->id, 'instance' => $ilddigitalcert->id), '*', MUST_EXIST);
        $ilddigitalcert = get_digitalcert($cm);

        $cert = certificate::new($cm, $user);
        $this->assertIsObject($cert);

        $openbadge = $cert->get_ob();
        $certfromob = certificate::from_ob($openbadge);
        $obfromob = $certfromob->get_ob();
        $this->assertEquals($openbadge, $obfromob);

        $edci = $cert->get_edci();
        $certfromedci = certificate::from_edci($edci);
        $edcifromedci = $certfromedci->get_edci();
        $this->assertEquals($edci, $edcifromedci);

        $obfromedci = certificate::from_edci($edci)->get_ob();

        $this->assertEquals($openbadge, $obfromedci);
    }

    public function test_issue_cert() {
        $this->resetAfterTest(true);
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $course = $this->getDataGenerator()->create_course();
        $issuer = $this->create_issuer();
        $ilddigitalcert = $this->getDataGenerator()->create_module('ilddigitalcert', array('course' => $course->id, 'issuer' => $issuer->id));
        $cm = $DB->get_record('course_modules', array('course' => $course->id, 'instance' => $ilddigitalcert->id), '*', MUST_EXIST);
        $ilddigitalcert = get_digitalcert($cm);

        $cert = certificate::new($cm, $user);
        $cert->issue($cm, 1, time());
        $this->assertIsObject($cert);

        $openbadge = $cert->get_ob();
        $certfromob = certificate::from_ob($openbadge);
        $obfromob = $certfromob->get_ob();
        $this->assertEquals($openbadge, $obfromob);

        $edci = $cert->get_edci();
        $certfromedci = certificate::from_edci($edci);
        $edcifromedci = $certfromedci->get_edci();
        $this->assertEquals($edci, $edcifromedci);

        $obfromedci = certificate::from_edci($edci)->get_ob();

        $this->assertEquals($openbadge, $obfromedci);
    }

    /**
     * Test function accepts parameters passed from the specified data provider.
     *
     * @dataProvider signcert_provider
     * @param array $certdata
     * @param array $issuerdata
     */
    public function test_signcert(array $certdata = array(), array $issuerdata = array()) {
        $this->resetAfterTest(true);
        global $DB;

        $this->create_config();
        $user = $this->getDataGenerator()->create_user();
        $certifier = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $course = $this->getDataGenerator()->create_course();
        $issuer = $this->create_issuer($issuerdata);
        $ilddigitalcert = $this->getDataGenerator()->create_module('ilddigitalcert', array_merge(array('course' => $course->id, 'issuer' => $issuer->id), $certdata));
        $cm = $DB->get_record('course_modules', array('course' => $course->id, 'instance' => $ilddigitalcert->id), '*', MUST_EXIST);
        $ilddigitalcert = get_digitalcert($cm);

        $cert = certificate::new($cm, $user);
        $this->assertIsObject($cert);

        $cert->issue($cm, 1, time());

        $cert->sign($certifier, $ilddigitalcert->course);

        // Add salt to openBadge cert.
        if (!$tokenid = save_token()) {
            $tokenid = 'error';
        }
        $salt = get_token($tokenid);
        $hash = $cert->get_ob_hash($salt);

        $cert->add_verification($hash);

        $openbadge = $cert->get_ob();
        $certfromob = certificate::from_ob($openbadge);
        $obfromob = $certfromob->get_ob();
        $this->assertEquals($openbadge, $obfromob);

        $json = json_decode($openbadge);
        $ob = json_decode($obfromob);

        $this->assertIsString($ob->id);
        $this->assertIsString($ob->issuedOn);
        $this->assertIsString($ob->{'extensions:assertionpageB4E'}->assertionpage);
        $this->assertIsObject($ob->{'extensions:signatureB4E'});
        $this->assertIsObject($ob->{'extensions:contractB4E'});
        $this->assertObjectNotHasAttribute('extensions:institutionTokenILD', $ob);
        $this->assertIsObject($ob->verification);
        if (isset($certifier->city) and $certifier->city != '') {
            $this->assertObjectHasAttribute('certificationplace', $ob->{'extensions:signatureB4E'});
            $this->assertIsString($ob->{'extensions:signatureB4E'}->certificationplace);
        } else {
            $this->assertObjectNotHasAttribute('certificationplace', $ob->{'extensions:signatureB4E'});
        }
        if (isset($issuer->pob)) {
            $this->assertObjectHasAttribute('pob', $ob->badge->issuer->{'extensions:addressB4E'});
            $this->assertIsString($ob->badge->issuer->{'extensions:addressB4E'}->pob);
        } else {
            $this->assertObjectNotHasAttribute('pob', $ob->badge->issuer->{'extensions:addressB4E'});
        }
        if (isset($certdata['examination_start']) && $certdata['examination_start'] > 0) {
            $this->assertObjectHasAttribute('startdate', $ob->{'extensions:examinationB4E'});
            $this->assertIsString($ob->{'extensions:examinationB4E'}->startdate);
        } else {
            $this->assertObjectNotHasAttribute('startdate', $ob->{'extensions:examinationB4E'});
        }
        if (isset($certdata['examination_end']) && $certdata['examination_end'] > 0) {
            $this->assertObjectHasAttribute('enddate', $ob->{'extensions:examinationB4E'});
            $this->assertIsString($ob->{'extensions:examinationB4E'}->enddate);
        } else {
            $this->assertObjectNotHasAttribute('enddate', $ob->{'extensions:examinationB4E'});
        }
        if (isset($certdata['examination_regulations'])) {
            $this->assertObjectHasAttribute('title', $ob->{'extensions:examinationRegulationsB4E'});
            $this->assertIsString($ob->{'extensions:examinationRegulationsB4E'}->title);
        } else {
            $this->assertObjectNotHasAttribute('title', $ob->{'extensions:examinationRegulationsB4E'});
        }
        if (isset($certdata['examination_regulations_url'])) {
            $this->assertObjectHasAttribute('url', $ob->{'extensions:examinationRegulationsB4E'});
            $this->assertIsString($ob->{'extensions:examinationRegulationsB4E'}->url);
        } else {
            $this->assertObjectNotHasAttribute('url', $ob->{'extensions:examinationRegulationsB4E'});
        }
        if (isset($certdata['examination_regulations_id'])) {
            $this->assertObjectHasAttribute('regulationsid', $ob->{'extensions:examinationRegulationsB4E'});
            $this->assertIsString($ob->{'extensions:examinationRegulationsB4E'}->regulationsid);
        } else {
            $this->assertObjectNotHasAttribute('regulationsid', $ob->{'extensions:examinationRegulationsB4E'});
        }
        if (isset($certdata['examination_regulations_date'])) {
            $this->assertObjectHasAttribute('date', $ob->{'extensions:examinationRegulationsB4E'});
            $this->assertIsString($ob->{'extensions:examinationRegulationsB4E'}->date);
        } else {
            $this->assertObjectNotHasAttribute('date', $ob->{'extensions:examinationRegulationsB4E'});
        }
        if (isset($json->badge->criteria)) {
            $this->assertObjectHasAttribute('criteria', $ob->badge);
        }
        if (!isset($ilddigitalcert->criteria) || empty($ilddigitalcert->criteria)) {
            $this->assertObjectNotHasAttribute('criteria', $ob->badge);
        } else {
            $this->assertIsString($ob->badge->criteria);
            $this->assertNotEmpty($ob->badge->criteria);
        }
        if (isset($json->badge->tags)) {
            $this->assertObjectHasAttribute('tags', $ob->badge);
            $this->assertIsArray($ob->badge->tags);
        }
        if (!isset($ilddigitalcert->tags) || empty($ilddigitalcert->tags)) {
            $this->assertObjectNotHasAttribute('tags', $ob->badge);
        } else {
            $this->assertIsArray($ob->badge->tags);
            $this->assertNotEmpty($ob->badge->tags);
            $this->assertEquals($ob->badge->tags[0], $ilddigitalcert->tags[0]);
        }

        $edci = $cert->get_edci();
        $certfromedci = certificate::from_edci($edci);
        $edcifromedci = $certfromedci->get_edci();
        $this->assertEquals($edci, $edcifromedci);

        $obfromedci = certificate::from_edci($edci)->get_ob();
        $this->assertEquals($openbadge, $obfromedci);

        $hash2 = $certfromedci->get_ob_hash($salt);
        $this->assertEquals($hash, $hash2);

        $cert->add_institutiontoken($salt);
        $obwithildtoken = json_decode($cert->get_ob());
        $this->assertObjectHasAttribute('extensions:institutionTokenILD', $obwithildtoken);
        $this->assertIsString($obwithildtoken->{'extensions:institutionTokenILD'}->institutionToken);
        $cert->get_ob_hash($salt);
        $obwithildtoken = json_decode($cert->get_ob());
        $this->assertObjectHasAttribute('extensions:institutionTokenILD', $obwithildtoken);
        $this->assertIsString($obwithildtoken->{'extensions:institutionTokenILD'}->institutionToken);

    }

    /**
     * Data provider for {@see self::test_signcert()}.
     *
     * @return array List of data sets - (string) data set name => (array) data
     */
    public function signcert_provider(): array {
        return [
            'Default' => [
                'certdata' => [],
            ],
            'With endates' => [
                'certdata' => [
                    'expiredate' => time(),
                    'expireperiod' => 5000,
                ]
            ],
            'With criteria' => [
                'certdata' => [
                    'criteria' => 'criteria, criteria, criteria, criteria',
                ]
            ],
            'With tags' => [
                'certdata' => [
                    'tags' => ["tag1", "tag2" , "tag3"],
                ]
            ],
            'With criteria and tags' => [
                'certdata' => [
                    'criteria' => 'criteria, criteria, criteria, criteria',
                    'tags' => ["tag1", "tag2" , "tag3"],
                ]
            ],
            'With empty expertise' => [
                'certdata' => [
                    'expertise' => '',
                ]
            ],
            'With examination data' => [
                'certdata' => [
                    'examination_regulations' => 'Regulation',
                    'examination_regulations_url' => 'https://regulation.com',
                    'examination_regulations_id' => '6',
                    'examination_regulations_date' => time(),
                    'examination_start' => time(),
                    'examination_end' => time() + 1,
                ]
            ],
            'With issuer data' => [
                'issuerdata' => [
                    'description' => 'DigiCerts bearbeitet die Fragestellung, wie Fälschungssicherheit sowie sicherer Zugang
                    und sichere Verwaltung von digitalen Bildungsnachweisen und Zertifikaten gemäß der Bedarfe von Lernenden, Unternehmen, Bildungseinrichtungen
                    und Zertifizierungsstellen langfristig gewährleistet werden kann.',
                    'pob' => '542323',
                ]
            ],
            'Complete' => [
                'certdata' => [
                    'expiredate' => time(),
                    'expireperiod' => 5000,
                    'criteria' => 'criteria, criteria, criteria, criteria',
                    'examination_regulations' => 'Regulation',
                    'examination_regulations_url' => 'https://regulation.com',
                    'examination_regulations_id' => '6',
                    'examination_regulations_date' => time(),
                    'examination_start' => time(),
                    'examination_end' => time() + 1,
                ],
                'issuerdata' => [
                    'description' => 'DigiCerts bearbeitet die Fragestellung, wie Fälschungssicherheit sowie sicherer Zugang
                    und sichere Verwaltung von digitalen Bildungsnachweisen und Zertifikaten gemäß der Bedarfe von Lernenden, Unternehmen, Bildungseinrichtungen
                    und Zertifizierungsstellen langfristig gewährleistet werden kann.',
                    'pob' => '542323',
                ],
            ]
        ];
    }

    private function create_config() {
        set_config('demo_mode', 1, 'ilddigitalcert');
        set_config('blockchain_url', 'http://quorum.th-luebeck.de:8545', 'ilddigitalcert');
        set_config('failover_url', 'http://quorum.th-luebeck.de:8545', 'ilddigitalcert');
    }

    private function create_issuer($issuerdata = array()) {
        global $DB;

        $issuer = array(
            'name' => 'Digicerts Demo-Zertifizierungsstelle',
            'location' => 'Lübeck',
            'zip' => '23552',
            'street' => 'Mönkhofer Weg 36',
            'email' => 'demo@digicerts.de',
            'url' => 'https://www.digicerts.de',
            'address' => 'd35d4a6e321d7af0d8502f9b695f25dc75ce47db',
        );

        if (!$id = $DB->insert_record('ilddigitalcert_issuer', array_merge($issuer, $issuerdata))) {
            throw new \coding_exception('Data Generator failed to insert a new ilddigitalcert_issuer record');
        }

        return $DB->get_record('ilddigitalcert_issuer', array('id' => $id), '*', MUST_EXIST);
    }
}
