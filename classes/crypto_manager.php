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

namespace mod_ilddigitalcert;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/../../../config.php');
require_login();
require($CFG->dirroot . '/mod/ilddigitalcert/vendor/autoload.php');

use Defuse\Crypto\Key;
use Defuse\Crypto\Crypto;

/**
 * Utility functions that provide en- and decryption functionalities
 *
 * Example usage:
 * $cipher = crypto_manager::encrypt($secret_data);
 * $decrypted_data = crypto_manager::decrypt($cipher);
 *
 * @package    mod_ilddigitalcert
 * @copyright  2022 ISy TH Lübeck <dev.ild@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class crypto_manager {
    /**
     * Loads the encryption key from a config file in moodledata.
     *
     * @return string Encryption key.
     */
    public static function loadencryptionkeyfromconfig() {
        global $CFG;
        $keyfile = $CFG->dataroot . '/filedir/ilddigitalcert-secret_key.txt';
        if (!file_exists($keyfile)) {
            throw new \coding_exception("Encryption key file is missing. A programmer has to generate a key first!
                See plugin installation guide for more infos.");
        }
        $keyascii = file_get_contents($keyfile);
        return Key::loadFromAsciiSafeString($keyascii);
    }

    /**
     * Encrypts a string with a set key.
     *
     * @param string $secret_data Secret to be encrypted.
     * @return string Encrypted data.
     */
    public static function encrypt($secretdata) {
        $key = self::loadEncryptionKeyFromConfig();
        return Crypto::encrypt($secretdata, $key);
    }

    /**
     * Decrypts a string with a set key.
     * Throws an error if $ciphertext wasn't encrypted with set key
     * or if the Ciphertext was modified.
     *
     * @param string $ciphertext Cipher to be decrypted.
     * @return string Decrypted secret.
     */
    public static function decrypt($ciphertext) {
        $key = self::loadEncryptionKeyFromConfig();
        try {
            return Crypto::decrypt($ciphertext, $key);
        } catch (\Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
            throw $ex;
        }
    }
}
