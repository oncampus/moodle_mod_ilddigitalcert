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
 * An assessment object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assessment
{
    /**
     * @var  int counter that gets incremented with every assessment object that gets created, used to generate a unique id.
     */
    private static $count = 0;

    /**
     * @var string Unique identifier.
     */
    private $id = "";

    /**
     * @var string Title.
     */
    private $title = "";

    /**
     * @var string Grade.
     */
    private $grade = "bestanden";

    /**
     * @var string Datetime the assessment started.
     */
    private $startdate = "";

    /**
     * @var string Datetime the assessment ended.
     */
    private $enddate = "";

    /**
     * @var string Place where the assessment took place.
     */
    private $place = "";

    /**
     * @var string id of the organization that assessed the assessment.
     */
    private $assessed_by = "";

    /**
     * @var assessmentSpec Further specifications.
     */
    private $spec;

    /**
     * Returns assessment Specification.
     *
     * @return assessmentSpec
     */
    public function get_spec()
    {
        return $this->spec;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
    }

    /**
     * Creates an assessment Object based on an edci certificate.
     *
     * @param mySimpleXMLElement $xml Contains the assessment information in edci format.
     * @return assessment
     */
    public static function from_edci($xml)
    {
        $assessment = new assessment();
        $ass_xml = $xml->credentialSubject->achievements->learningAchievement[0]->wasDerivedFrom;
        $assessment->id = $ass_xml['id'];

        $assessment->title = (string) $ass_xml->title->text;

        // TODO: get grade
        $assessment->grade = (string) $ass_xml->grade;
        $assessment->startdate = (string) $ass_xml->startdate;
        $assessment->enddate = (string) $ass_xml->enddate;
        $assessment->place = (string) $ass_xml->place;
        $assessment->assessed_by = $ass_xml->assessedBy['idref'];

        $assessment->spec = assessmentSpec::from_edci($xml);
        return $assessment;
    }

    /**
     * Creates an assessment Object based on an openBadge certificate.
     *
     * @param certificate $bcert certificate object that references this assessment.
     * @param mySimpleXMLElement $json Contains the assessment information in openBadge format.
     * @return assessment
     */
    public static function from_ob($bcert, $json)
    {
        $assessment = new assessment();
        self::$count += 1;
        $assessment->id = 'urn:bcert:assessment:' . self::$count;
        if (isset($json->{'extensions:examinationB4E'}->title)) {
            $assessment->title = $json->{'extensions:examinationB4E'}->title;
        } else {
            $assessment->title = $json->badge->name;
        }

        // TODO: get grade
        $assessment->startdate = manager::get_if_object_key_exists($json->{'extensions:examinationB4E'}, 'startdate');
        $assessment->enddate = manager::get_if_object_key_exists($json->{'extensions:examinationB4E'}, 'endate');
        $assessment->place = manager::get_if_object_key_exists($json->{'extensions:examinationB4E'}, 'place');
        $assessment->assessed_by = $bcert->get_issuer()->get_id();

        $assessment->spec = assessmentSpec::from_ob($json);
        return $assessment;
    }

    /**
     * Returns a default Object containing assessment data in openBadge format.
     *
     * @return object
     */
    public function get_ob()
    {
        $assessment = new \stdClass();
        $assessment->title = $this->title;
        $assessment->startdate = $this->startdate;
        $assessment->enddate = $this->enddate;
        $assessment->place = $this->place;
        $assessment->{'@context'} = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/ExaminationB4E/context.json';
        $assessment->type = ["Extension", "ExaminationB4E"];
        return $assessment;
    }

    /**
     * Returns a mySimpleXMLElement containing verification data in edci format.
     *
     * @return mySimpleXMLElement
     */
    public function get_edci()
    {
        $root = mySimpleXMLElement::create_empty('wasDerivedFrom');
        $root->addAttribute('id', $this->id);
        $root->addTextNode('title', $this->title);
        $root->addChild('grade', $this->grade);
        $root->addChild('assessedBy')->addAttribute('idref', $this->assessed_by);
        $root->addChild('specifiedBy')->addAttribute('idref', $this->spec->get_id());
        $root->addChild('startdate', $this->startdate);
        $root->addChild('enddate', $this->enddate);
        $root->addChild('place', $this->place);

        return $root;
    }
}
