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
 * A assessmentSpec object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assessmentSpec
{
    /**
     * @var  int counter that gets incremented with every assessmentSpec object that gets created, used to generate a unique id.
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
     * @var string Identifier unsed in edci.
     */
    private $identifier = "";

    /**
     * @var string Url to leading to further assessment info if available.
     */
    private $homepage = "";

    /**
     * @var string Datetime the assessment took place.
     */
    private $date = "";

    /**
     * Returns id.
     *
     * @return string
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
    }

    /**
     * Creates a assessmentSpec Object based on an edci certificate.
     *
     * @param mySimpleXMLElement $xml Contains the assessment specification information in edci format.
     * @return assessmentSpec
     */
    public static function from_edci($xml)
    {
        $new = new assessmentSpec();
        $spec_xml = $xml->assessmentSpecificationReferences->assessmentSpecification;
        $new->id = $spec_xml['id'];
        $new->title = (string) $spec_xml->title->text;
        $new->homepage = (string) $spec_xml->homepage['uri'];
        $new->identifier = (string) $spec_xml->identifier;
        $new->date = (string) $spec_xml->date;
        return $new;
    }

    /**
     * Creates a assessmentSpec Object based on an openBadge certificate.
     *
     * @param mySimpleXMLElement $json Contains the assessment specification information in openBadge format.
     * @return assessmentSpec
     */
    public static function from_ob($json)
    {
        $new = new assessmentSpec();
        self::$count += 1;
        $new->id = 'urn:bcert:asssessmentspec:' . self::$count;
        $new->title = $json->{'extensions:examinationRegulationsB4E'}->title;
        $new->homepage = $json->{'extensions:examinationRegulationsB4E'}->url;
        $new->identifier = $json->{'extensions:examinationRegulationsB4E'}->regulationsid;
        $new->date = manager::get_if_object_key_exists($json->{'extensions:examinationRegulationsB4E'}, 'date');
        return $new;
    }


    /**
     * Returns a default Object containing assessment specification data in openBadge format.
     *
     * @return object
     */
    public function get_ob()
    {
        $spec = new \stdClass();
        $spec->title = $this->title;
        $spec->regulationsid = $this->identifier;
        $spec->url = $this->homepage;
        $spec->date = $this->date;
        $spec->{'@context'} = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/ExaminationRegulationsB4E/context.json';
        $spec->type = ["Extension", "ExaminationRegulationsB4E"];
        return $spec;
    }

    /**
     * Returns a mySimpleXMLElement containing assessment specification data in edci format.
     *
     * @return mySimpleXMLElement
     */
    public function get_edci()
    {
        $root = mySimpleXMLElement::create_empty('assessmentSpecification');
        $root->addAttribute('id', $this->id);
        $root->addChild('identifier', $this->identifier);
        $root->addTextNode('title', $this->title);
        $root->addChild('homepage')->addAttribute('uri', $this->homepage);
        $root->addChild('date', $this->date);

        return $root;
    }
}
