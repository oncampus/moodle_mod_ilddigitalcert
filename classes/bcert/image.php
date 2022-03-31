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
 * A image object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class image {


    /**
     * @var string Name of the edci xml node.
     */
    private $name;

    /**
     * @var string URI that represents the filetype of the image in the edci certificate.
     */
    private $typeuri = "http://publications.europa.eu/resource/authority/file-type/";

    /**
     * @var string File type.
     */
    private $typename;

    /**
     * @var string base64 encoded image data.
     */
    private $content;

    /**
     * Constructor.
     */
    private function __construct() {
    }

    /**
     * Creates an image object base on a given moodle file.
     *
     * @param \file $file
     * @param string $name
     * @return \stored_file
     */
    public static function new($file, $name) {
        if (!isset($file)) {
            return null;
        }

        $new = new self();

        $new->name = $name;
        $new->content = base64_encode($file->get_content());
        $new->typename = str_replace('IMAGE/', '', strtoupper($file->get_mimetype()));

        return $new;
    }

    /**
     * Creates an image Object based on an edci certificate.
     *
     * @param mySimpleXMLElement $xml Contains the certificate image in edci format.
     * @return image
     */
    public static function from_edci($xml) {
        if (empty($xml)) {
            return null;
        }

        $image = new image();

        $image->name = $xml->getName();
        $image->typename = $xml->contentType->targetName->text;
        $image->content = $xml->content;

        return $image;
    }

    /**
     * Creates an image Object based on an openBadge certificate.
     *
     * @param string $name string that is ment to be used as the root node name in the edci xml.
     * @param string $image_data base64 encoded image data.
     * @return image
     */
    public static function from_ob($name, $imagedata) {
        if (empty($imagedata)) {
            return null;
        }

        $image = new image();

        // Search for file type and content of the image data.
        $match = [];
        preg_match('/data:image\/(.+);base64,(.+)/', $imagedata, $match);

        $image->name = $name;
        $image->typename = strtoupper($match[1]);
        $image->content .= $match[2];
        return $image;
    }


    /**
     * Returns a String containing base64 encoded image data for use in an openBadge cert.
     *
     * @return string
     */
    public function get_ob() {
        return 'data:image/' . strtolower($this->typename) . ';base64,' . $this->content;
    }


    /**
     * Returns a mySimpleXMLElement containing image data in edci format.
     *
     * @return mySimpleXMLElement
     */
    public function get_edci() {
        $root = mySimpleXMLElement::create_empty($this->name);

        $contenttype = $root->addChild('contentType');
        $contenttype->addAttribute('targetFrameworkUrl', 'http://publications.europa.eu/resource/authority/file-type');
        $contenttype->addAttribute('targetNotation', 'file-type');
        $contenttype->addAttribute('uri', $this->typeuri . $this->typename);
        $contenttype->addtextnode('targetName', $this->typename, 'en');
        $contenttype->addtextnode('targetFrameworkName', 'File type', 'en');

        $contentencoding = $root->addChild('contentEncoding');
        $contentencoding->addAttribute('targetFrameworkUrl', 'http://data.europa.eu/snb/encoding/25831c2');
        $contentencoding->addAttribute('uri', 'http://data.europa.eu/snb/encoding/6146cde7dd');
        $contentencoding->addtextnode('targetName', 'Base64', 'en');
        $contentencoding->addtextnode('targetFrameworkName', 'Europass Standard List of Content Encoding Types', 'en');

        $root->addChild('content', $this->content);

        return $root;
    }
}
