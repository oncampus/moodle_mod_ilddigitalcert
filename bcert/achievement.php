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
 * A Achievement object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Achievement
{
    /**
     * @var  int counter that gets incremented with every Achievement object that gets created, used to generate a unique id.
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
     * @var string Assessment.
     */
    private $assessment;

    /**
     * @var string Qualification.
     */
    private $qualification;

    /**
     * Returns title.
     *
     * @return string
     */
    public function get_expertise()
    {
        return $this->title;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
    }


     /**
     * Creates an Achievement Object based on an edci certificate.
     *
     * @param BCert $bcert BCert object that references this achievement.
     * @param MySimpleXMLElement $xml Contains the achievement information in edci format.
     * @return Achievement
     */
    public static function from_edci($bcert, $xml)
    {
        $new = new Achievement();
        $new->id = $xml['id'];
        $new->title = (string) $xml->title->text;
        $new->assessment = $bcert->get_assessment();
        $new->qualification = $bcert->get_qualification();
        return $new;
    }

    /**
     * Creates an Achievement Object based on an openBadge certificate.
     *
     * @param BCert $bcert BCert object that references this achievement.
     * @param MySimpleXMLElement $json Contains the achievement information in openBadge format.
     * @return Assessment
     */
    public static function from_ob($bcert, $title)
    {
        $new = new Achievement();
        self::$count += 1;
        $new->id = 'urn:bcert:learningAchievement:' . self::$count;
        $new->title = $title;
        $new->assessment = $bcert->get_assessment();
        $new->qualification = $bcert->get_qualification();
        return $new;
    }

    /**
     * Returns a MySimpleXMLElement containing achievement data in edci format.
     *
     * @return MySimpleXMLElement
     */
    public function get_edci()
    {
        $root = MySimpleXMLElement::create_empty('learningAchievement');
        $root->addAttribute('id', $this->id);
        $root->addTextNode('title', $this->title);

        $root->appendXML($this->assessment->get_edci());

        // TODO: get id from $qualification
        $root->addChild('specifiedBy')->addAttribute('idref', $this->qualification->get_id());

        return $root;
    }
}