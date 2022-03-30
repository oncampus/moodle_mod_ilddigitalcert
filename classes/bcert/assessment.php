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
 * An assessment object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assessment {

    /** @var  int counter that gets incremented with every assessment object that gets created, used to generate a unique id. */
    private static $count = 0;

    /**  @var string Unique identifier. */
    private $id;

    /** @var string Title. */
    private $title;

    /** @var string Grade. */
    private $grade = "bestanden";

    /** @var string Datetime the assessment started. Optional attribute. */
    private $startdate;

    /** @var string Datetime the assessment ended. Optional attribute. */
    private $enddate;

    /** @var string Place where the assessment took place. Optional attribute. */
    private $place;

    /** @var assessment_spec Further specifications. */
    private $spec;

    /**
     * Returns assessment Specification.
     *
     * @return assessment_spec
     */
    public function get_spec() {
        return $this->spec;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        self::$count += 1;
    }

    /**
     * Creates an assessment object based on the examination info set in an ilddigitalcert course module.
     *
     * @param certificate $cert
     * @param \stdClass $ilddigitalcert
     * @return assessment
     */
    public static function new($cert, $ilddigitalcert) {
        $new = new self();
        $new->certificate = $cert;
        $new->id = 'urn:bcert:assessment:' . self::$count;
        $new->title = $ilddigitalcert->name;
        if (isset($ilddigitalcert->examination_start) && $ilddigitalcert->examination_start > 0) {
            $new->startdate = date('c', $ilddigitalcert->examination_start);
        }
        if (isset($ilddigitalcert->examination_end) && $ilddigitalcert->examination_end > 0) {
            $new->enddate = date('c', $ilddigitalcert->examination_end);
        }
        $new->place = manager::get_if_key_exists($ilddigitalcert, 'examination_place');

        $new->spec = assessment_spec::new($ilddigitalcert);
        return $new;
    }

    /**
     * Creates an assessment Object based on an edci certificate.
     *
     * @param certificate $cert certificate object that references this assessment.
     * @param mySimpleXMLElement $xml Contains the assessment information in edci format.
     * @return assessment
     */
    public static function from_edci($cert, $xml) {
        $new = new self();
        $new->certificate = $cert;

        $assxml = $xml->credentialSubject->achievements->learningAchievement[0]->wasDerivedFrom;

        $new->id = $assxml['id'];

        $new->title = (string) $assxml->title->text;

        $new->grade = (string) $assxml->grade;

        if (isset($assxml->startDate)) {
            $new->startdate = (string) $assxml->startDate;
        }
        if (isset($assxml->endDate)) {
            $new->enddate = (string) $assxml->endDate;
        }
        if (isset($assxml->place)) {
            $new->place = (string) $assxml->place->text;
        }

        $new->assessedby = $assxml->assessedBy['idref'];

        $new->spec = assessment_spec::from_edci($xml);
        return $new;
    }

    /**
     * Creates an assessment Object based on an openBadge certificate.
     *
     * @param certificate $cert certificate object that references this assessment.
     * @param \stdClass $json Contains the assessment information in openBadge format.
     * @return assessment
     */
    public static function from_ob($cert, $json) {
        $new = new self();
        $new->certificate = $cert;
        $new->id = 'urn:bcert:assessment:' . self::$count;
        if (isset($json->{'extensions:examinationB4E'}->title)) {
            $new->title = $json->{'extensions:examinationB4E'}->title;
        } else {
            $new->title = $json->badge->name;
        }

        // TODO: get grade.

        $new->startdate = manager::get_if_key_exists($json->{'extensions:examinationB4E'}, 'startdate');
        $new->enddate = manager::get_if_key_exists($json->{'extensions:examinationB4E'}, 'enddate');
        $new->place = manager::get_if_key_exists($json->{'extensions:examinationB4E'}, 'place');
        $new->assessedby = $cert->get_issuer()->get_id();

        $new->spec = assessment_spec::from_ob($json);
        return $new;
    }

    /**
     * Returns a default Object containing assessment data in openBadge format.
     *
     * @return \stdClass
     */
    public function get_ob() {
        $assessment = new \stdClass();
        $assessment->{'@context'} = \mod_ilddigitalcert\bcert\manager::CONTEXT_B4E_EXAMINATION;
        $assessment->type = ["Extension", "ExaminationB4E"];

        // TODO: set grade.

        if (isset($this->startdate)) {
            $assessment->startdate = $this->startdate;
        }
        if (isset($this->enddate)) {
            $assessment->enddate = $this->enddate;
        }
        if (isset($this->place)) {
            $assessment->place = $this->place;
        }

        return $assessment;
    }

    /**
     * Returns a mySimpleXMLElement containing verification data in edci format.
     *
     * @return mySimpleXMLElement
     */
    public function get_edci() {
        $root = mySimpleXMLElement::create_empty('wasDerivedFrom');
        $root->addAttribute('id', $this->id);
        $root->addtextnode('title', $this->title);
        $root->addChild('grade', $this->grade);
        $root->addChild('assessedBy')->addAttribute('idref', $this->certificate->get_issuer()->get_id());
        $root->addChild('specifiedBy')->addAttribute('idref', $this->spec->get_id());
        if (isset($this->startdate)) {
            $root->addChild('startDate', $this->startdate);
        }
        if (isset($this->enddate)) {
            $root->addChild('endDate', $this->enddate);
        }
        if (isset($this->place)) {
            $root->addtextnode('place', $this->place);
        }

        return $root;
    }
}
