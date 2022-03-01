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
 * A image object represents data that is essential for both
 * openbadge and edci certificats and helps convert beween the two standards.
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class image
{

    /**
     * @var string Name of the edci xml node.
     */
    private $name = "";

    /**
     * @var string URI that represents the filetype of the image in the edci certificate.
     */
    private $type_uri = "http://publications.europa.eu/resource/authority/file-type/";

    /**
     * @var string File type.
     */
    private $type_name = "";

    /**
     * @var string base64 encoded image data.
     */
    private $content = "";

    /**
     * Constructor.
     */
    private function __construct()
    {
    }

    /**
     * Creates an image Object based on an edci certificate.
     *
     * @param mySimpleXMLElement $xml Contains the certificate image in edci format.
     * @return image
     */
    public static function from_edci($xml)
    {
        if(empty($xml)) {
            return null;
        }
        
        $image = new image();

        $image->name = $xml->getName();
        $image->type_uri = $xml->contentType['uri'];
        $image->type_name = $xml->contentType->targetName->text;
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
    public static function from_ob($name, $image_data)
    {
        if(empty($image_data)) {
            return null;
        }

        $image = new image();

        // searching for file type and content of the image data
        $match = [];
        preg_match('/data:image\/(.+);base64,(.+)/', $image_data, $match);

        $image->name = $name;
        $image->type_name = strtoupper($match[1]);
        $image->type_uri .= $image->type_name;
        $image->content .= $match[2];
        return $image;
    }


    /**
     * Returns a String containing base64 encoded image data for use in an openBadge cert.
     *
     * @return string
     */
    public function get_ob()
    {
        return 'data:image/' . strtolower($this->type_name) . ';base64,' . $this->content;
    }


    /**
     * Returns a mySimpleXMLElement containing image data in edci format.
     *
     * @return mySimpleXMLElement
     */
    public function get_edci()
    {
        $root = mySimpleXMLElement::create_empty($this->name);

        $content_type = $root->addChild('contentType');
        $content_type->addAttribute('targetFrameworkUrl', 'http://publications.europa.eu/resource/authority/file-type');
        $content_type->addAttribute('targetNotation', 'file-type');
        $content_type->addAttribute('uri', $this->type_uri);
        $content_type->addTextNode('targetName', $this->type_name, 'en');
        $content_type->addTextNode('targetFrameworkName', 'File type', 'en');

        $content_encoding = $root->addChild('contentEncoding');
        $content_encoding->addAttribute('targetFrameworkUrl', 'http://data.europa.eu/snb/encoding/25831c2');
        $content_encoding->addAttribute('uri', 'http://data.europa.eu/snb/encoding/6146cde7dd');
        $content_encoding->addTextNode('targetName', 'Base64', 'en');
        $content_encoding->addTextNode('targetFrameworkName', 'Europass Standard List of Content Encoding Types', 'en');

        $root->addChild('content', $this->content);

        return $root;
    }
}
