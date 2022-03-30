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

use SimpleXMLElement;

/**
 * mySimpleXMLElement extends the SimpleXMLElement with new convenience functionality.
 *
 *
 * @package     mod_ilddigitalcert
 * @copyright   2020 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mySimpleXMLElement extends SimpleXMLElement {

    /**
     * Creates a mySimpleXMLElement including only a root tag of name $name.
     *
     * @param string $name name of xml root tag.
     * @return mySimpleXMLElement
     */
    public static function create_empty(string $name) {
        return new static("<$name/>");
    }

    /**
     * Add SimpleXMLElement code into a mySimpleXMLElement
     *
     * @param SimpleXMLElement $append SimpleXMLElement that is supposed to be
     * appended to the current element as a child.
     */
    public function appendxml($append) {
        if ($append) {
            // If the xml node $append has no text content, add empty child node.
            if (strlen(trim((string)$append)) == 0) {
                $xml = $this->addChild($append->getName());
            } else { // Else create node cotaining the text content.
                $xml = $this->addChild($append->getName(), manager::xml_escape((string)$append));
            }

            foreach ($append->attributes() as $n => $v) {
                $xml->addAttribute($n, $v);
            }

            // Recursive call for every child node of $append.
            foreach ($append->children() as $child) {
                $xml->appendXML($child);
            }
        }
    }

    /**
     * Add Node of name $name with a specific schema meant for decribing text content and its language.
     *
     * @param string $name name ist the name of the outer node.
     * @param string $content is the text cotent of the inner 'text' node.
     * @param string|null $language decribes the value of a the 'lang' attribute of the text node, defaults to 'de'.
     * @return void
     */
    public function addtextnode($name, $content, $language = 'de') {
        $child = $this->addChild($name, '');
        $text = $child->addChild('text', $content);
        $text->addAttribute('content-type', 'text/plain');
        $text->addAttribute('lang', $language);
    }
}
