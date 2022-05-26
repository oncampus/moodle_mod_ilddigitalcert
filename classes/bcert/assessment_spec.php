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
 * A assessment_spec object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assessment_spec {

    /** @var  int counter that gets incremented with every assessment_spec object that gets created, used to generate a unique id. */
    private static $count = 0;

    /** @var string Id used in edci only.  */
    private $id;

    /** @var string Unique identifier. Optional attribute. */
    private $identifier;

    /** @var string Title. Optional attribute. */
    private $title;

    /** @var string Url leading to further assessment info. Optional attribute. */
    private $homepage;

    /** @var string Datetime the assessment took place. Optional attribute. */
    private $date;

    /**
     * Returns id.
     *
     * @return string
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        self::$count += 1;
    }

    /**
     * Creates an assessment_spec object based on the examination data set in an ilddigtalcert course module.
     *
     * @param \stdClass $ilddigitalcert
     * @return assessment_spec
     */
    public static function new($ilddigitalcert) {
        $new = new self();
        $new->id = 'urn:bcert:asssessmentspec:' . self::$count;
        if (isset($ilddigitalcert->examination_regulations_id)) {
            $new->identifier = $ilddigitalcert->examination_regulations_id;
        }
        if (isset($ilddigitalcert->examination_regulations)) {
            $new->title = $ilddigitalcert->examination_regulations;
        }
        if (isset($ilddigitalcert->examination_regulations_url)) {
            $new->homepage = $ilddigitalcert->examination_regulations_url;
        }
        if ($ilddigitalcert->examination_regulations_date > 0) {
            $new->date = date('c', $ilddigitalcert->examination_regulations_date);
        }
        return $new;
    }

    /**
     * Creates an assessment_spec object based on an edci certificate.
     *
     * @param mySimpleXMLElement $xml Contains the assessment specification information in edci format.
     * @return assessment_spec
     */
    public static function from_edci($xml) {
        $new = new self();
        $specxml = $xml->assessmentSpecificationReferences->assessmentSpecification;
        $new->id = $specxml['id'];

        if (isset($specxml->title->text)) {
            $new->title = (string) $specxml->title->text;
        }
        if (isset($specxml->homepage['uri'])) {
            $new->homepage = (string) $specxml->homepage['uri'];
        }
        if (isset($specxml->identifier)) {
            $new->identifier = (string) $specxml->identifier;
        }

        if (isset($specxml->date)) {
            $new->date = (string) $specxml->date;
        }
        return $new;
    }

    /**
     * Creates a assessment_spec Object based on an openBadge certificate.
     *
     * @param \stdClass $json Contains the assessment specification information in openBadge format.
     * @return assessment_spec
     */
    public static function from_ob($json) {
        $new = new self();
        $new->id = 'urn:bcert:asssessmentspec:' . self::$count;
        $new->title = manager::get_if_key_exists($json->{'extensions:examinationRegulationsB4E'}, 'title');
        $new->homepage = manager::get_if_key_exists($json->{'extensions:examinationRegulationsB4E'}, 'url');
        $new->identifier = manager::get_if_key_exists($json->{'extensions:examinationRegulationsB4E'}, 'regulationsid');
        $new->date = manager::get_if_key_exists($json->{'extensions:examinationRegulationsB4E'}, 'date');
        return $new;
    }


    /**
     * Returns a default Object containing assessment specification data in openBadge format.
     *
     * @return object
     */
    public function get_ob() {
        $spec = new \stdClass();

        if (isset($this->title)) {
            $spec->title = $this->title;
        }
        if (isset($this->identifier)) {
            $spec->regulationsid = $this->identifier;
        }
        if (isset($this->homepage)) {
            $spec->url = $this->homepage;
        }
        if (isset($this->date)) {
            $spec->date = $this->date;
        }
        $spec->{'@context'} = \mod_ilddigitalcert\bcert\manager::CONTEXT_B4E_EXAMINATION_REGULATIONS;
        $spec->type = ["Extension", "ExaminationRegulationsB4E"];
        return $spec;
    }

    /**
     * Returns a mySimpleXMLElement containing assessment specification data in edci format.
     *
     * @return mySimpleXMLElement
     */
    public function get_edci() {
        $root = mySimpleXMLElement::create_empty('assessmentSpecification');
        $root->addAttribute('id', $this->id);
        if (isset($this->identifier)) {
            $root->addChild('identifier', $this->identifier);
        }
        if (isset($this->title)) {
            $root->addtextnode('title', $this->title);
        }
        if (isset($this->homepage)) {
            $root->addChild('homepage')->addAttribute('uri', $this->homepage);
        }
        if (isset($this->date)) {
            $root->addChild('date', $this->date);
        }

        return $root;
    }
}
