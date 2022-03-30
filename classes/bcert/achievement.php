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
 * A achievement object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class achievement {

    /** @var int counter that gets incremented with every achievement object that gets created, used to generate a unique id. */
    private static $count = 0;

    /** @var string Unique identifier. */
    private $id;

    /** @var string Title. */
    private $title;


    /** @var certifiacte $certificate Refernece to parent certificate object. */
    private $certificate;

    /**
     * Returns title.
     *
     * @return string
     */
    public function get_expertise() {
        return $this->title;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        self::$count += 1;
    }

    /**
     * Creates an achievement object based on a given expertise that the certificate_subject has achieved.
     *
     * @param certificate $cert
     * @param string $competence
     * @return achievement
     */
    public static function new($cert, $expertise) {
        $new = new self();
        $new->certificate = $cert;
        $new->id = 'urn:bcert:learningAchievement:' . self::$count;
        $new->title = $expertise;
        return $new;
    }


    /**
     * Creates an achievement Object based on an edci certificate.
     *
     * @param certificate $cert certificate object that references this achievement.
     * @param mySimpleXMLElement $xml Contains the achievement information in edci format.
     * @return achievement
     */
    public static function from_edci($cert, $xml) {
        $new = new self();
        $new->certificate = $cert;
        $new->id = $xml['id'];
        $new->title = (string) $xml->title->text;
        return $new;
    }

    /**
     * Creates an achievement Object based on an openBadge certificate.
     *
     * @param certificate $cert certificate object that references this achievement.
     * @param \stdClass $json Contains the achievement information in openBadge format.
     * @return assessment
     */
    public static function from_ob($cert, $title) {
        $new = new self();
        $new->certificate = $cert;
        $new->id = 'urn:bcert:learningAchievement:' . self::$count;
        $new->title = $title;
        return $new;
    }

    /**
     * Returns a mySimpleXMLElement containing achievement data in edci format.
     *
     * @return mySimpleXMLElement
     */
    public function get_edci() {
        $root = mySimpleXMLElement::create_empty('learningAchievement');
        $root->addAttribute('id', $this->id);
        $root->addtextnode('title', $this->title);

        $root->appendXML($this->certificate->get_assessment()->get_edci());

        $root->addChild('specifiedBy')->addAttribute('idref', $this->certificate->get_qualification()->get_id());

        return $root;
    }
}
