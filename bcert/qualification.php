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


defined('MOODLE_INTERNAL') || die();

require_once('mySimpleXMLElement.php');

/**
 * A Qualification object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Qualification
{
    /**
     * @var string XML that has to be included in the edci to describe the type of the qualification.
     */
    const QUAL_TYPE = '<type targetFrameworkUrl="http://data.europa.eu/snb/learning-opportunity/25831c2" uri="http://data.europa.eu/snb/learning-opportunity/05053c1cbe">
                            <targetName>
                                <text content-type="text/plain" lang="en">Course</text>
                            </targetName>
                        </type>';

    /**
     * @var string XML that has to be included in the edci to describe the title of the qualification.
     */
    const QUAL_TITLE = '<title>
                            <text content-type="text/plain" lang="en">Joint achievement</text>
                        </title>';

    /**
     * @var  int counter that gets incremented with every Qualification object that gets created, used to generate a unique id.
     */
    private static $count = 0;

    /**
     * @var string used as an unique identifier for the qualification.
     */
    private $id = "";

    // private $ects;

     /**
     * Returns the id.
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
     * Creates a Qualification Object based on an edci certificate.
     *
     * @param MySimpleXMLElement $xml Contains the qualification information in edci format.
     * @return Qualification
     */
    public static function from_edci($xml)
    {
        $new = new Qualification();
        $new->id = $xml->learningSpecificationReferences->qualification['id'];
        // $this->ects = $data->->ects;
        return $new;
    }

    /**
     * Creates a Qualification Object based on an openBadge certificate.
     *
     * @param MySimpleXMLElement $json Contains the qualification information in openBadge format.
     * @return Qualification
     */
    public static function from_ob($json)
    {
        $new = new Qualification();
        self::$count += 1;
        $new->id = 'urn:bcert:qualification:' . self::$count;
        // $this->ects = $data->->ects;
        return $new;
    }

    /**
     * Returns a MySimpleXMLElement containing qualification data in edci format.
     *
     * @return MySimpleXMLElement
     */
    public function get_edci()
    {
        $root = MySimpleXMLElement::create_empty('qualification');
        $root->addAttribute('id', $this->id);

        $root->appendXML(new MySimpleXMLElement(Qualification::QUAL_TYPE));
        $root->appendXML(new MySimpleXMLElement(Qualification::QUAL_TITLE));

        // $root->addChild('hasECTSCreditPoints', $this->ects);

        return $root;
    }
}