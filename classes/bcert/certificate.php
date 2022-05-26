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
 * The bcert class reflects the data of a blockchain certificate
 * and handles the conversion process between openBadge and edci formats.
 *
 * An bcert object takes an existing blockchain certificate in either openBadge
 * or edci format. The bcert object offers methods to generate obenBadge
 * and edci certificats.
 *
 *
 * @package     mod_ilddigitalcert
 * @copyright   2021 ILD TH Lübeck <dev.ild@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate {


    /** w3c verifiable credentials namespace. */
    const CRED_NAMESPACE = 'http://data.europa.eu/europass/model/credentials/w3c#';

    /** @var array An array of organizations involved in the issuing process. */
    private $agents = [];

    /**  @var string base64 encoded pdf content. */
    private $assertionpage;

    /** @var assessment assessment that is rewarded with this certificate. */
    private $assessment;

    /** @var contract Smart contract. */
    private $contract;

    /** @var credential_subject The credential subject or holder. */
    private $credentialsubject;

    /** @var string Prerequisites for entering a course. Optional attribute.*/
    private $criteria;

    /** @var string Description. Optional attribute. */
    private $description;

    /** @var string A url that links to the original record of a certificate on moodle system the cert was first issued on. */
    private $identifier;

    /** @var image image that is used in pdf representation. Optional attribute. */
    private $image;

    /** @var string A token unique to the issuing institution. Optional attribute. */
    private $institutiontoken;

    /** @var string Datetime the certificate was issued. */
    private $issuedon;

    /** @var organization The issuing institution. */
    private $issuer;

    /** @var qualification Qualification info about the subject. */
    private $qualification;

    /** @var signature Signature of the certifier. */
    private $signature;

    /**  @var array Tags for searchability of moodle courses. Optional attribute.*/
    private $tags;

    /** @var string Title. */
    private $title;

    /** @var string Datetime the certificates validity will expire. Optional attribute. */
    private $validuntil;

    /** @var verification object containing data needed for verifying validity. */
    private $verification;

    /**
     * Getter for $this->validuntil.
     *
     * @return string
     */
    public function get_validuntil() {
        return $this->validuntil;
    }

    /**
     * Getter for $this->issuedon.
     *
     * @return string
     */
    public function get_issuedon() {
        return $this->issuedon;
    }

    /**
     * Getter for $credentialsubject.
     *
     * @return credential_subject
     */
    public function get_credentialsubject() {
        return $this->credentialsubject;
    }

    /**
     * Returns the id of the credential_subject.
     *
     * @return string
     */
    public function get_subjectid() {
        return $this->credentialsubject->get_identifier();
    }

    /**
     * Returns the certificate title.
     *
     * @return string
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Returns the issuing organization.
     *
     * @return organization
     */
    public function get_issuer() {
        return $this->issuer;
    }

    /**
     * Returns the assessment.
     *
     * @return assessment
     */
    public function get_assessment() {
        return $this->assessment;
    }

    /**
     * Returns the qualification.
     *
     * @return qualification
     */
    public function get_qualification() {
        return $this->qualification;
    }

    /**
     * Constructor.
     */
    private function __construct() {
    }

    /**
     * Creates a certificate object based on an ilddigitalcert with $cmid for a user with $userid.
     *
     * @param \stdClass $cm The course module that provides the certification functionality to a course.
     * @param \core_user $recipient The recipient/subject of the certificate.
     * @return certificate
     */
    public static function new($cm, $recipient) {
        global $DB;
        $new = new self();

        $ilddigitalcert = get_digitalcert($cm);
        $issuer = $DB->get_record('ilddigitalcert_issuer', array('id' => $ilddigitalcert->issuer), '*', MUST_EXIST);

        $new->assessment = assessment::new($new, $ilddigitalcert);
        $new->credentialsubject = credential_subject::new($new, $recipient, $ilddigitalcert->expertise);
        $new->description = $ilddigitalcert->description;
        $new->image = image::new(\mod_ilddigitalcert\bcert\manager::get_certificate_image($cm->id), 'image');
        $new->issuer = organization::issuer($issuer);
        $new->agents[] = $new->issuer;
        $new->qualification = qualification::new();
        $new->title = $ilddigitalcert->name;
        $new->validuntil = get_expiredate($ilddigitalcert->expiredate, $ilddigitalcert->expireperiod);
        // TODO: Implement tags and criteria in the ob and edci versions of the certificate
        // TODO: Extend the edci-schema with tags and criteria.
        if (!empty($ilddigitalcert->tags)) {
            $new->tags = $ilddigitalcert->tags;
        }
        if (!empty($ilddigitalcert->criteria)) {
            $new->criteria = $ilddigitalcert->criteria;
        }
        return $new;
    }

    /**
     * Creates a certificate object based on an edci certificate.
     *
     * @param string $xml_string Contains blockchain certificate information in edci format.
     * @return certificate
     */
    public static function from_edci($xmlstring) {
        $xml = new mySimpleXMLElement($xmlstring);

        $cert = new certificate();
        if (isset($xml->identifier)) {
            $cert->identifier = (string) $xml->identifier;
        }
        if (isset($xml->children(self::CRED_NAMESPACE)->issued)) {
            $cert->issuedon = (string) $xml->children(self::CRED_NAMESPACE)->issued;
        }
        if (isset($xml->children(self::CRED_NAMESPACE)->validUntil)) {
            $cert->validuntil = (string) $xml->children(self::CRED_NAMESPACE)->validUntil;
        }
        $cert->title = (string) $xml->title->text;

        if (isset($xml->description->text)) {
            $cert->description = (string) $xml->description->text;
        }
        $cert->issuer = organization::from_edci($xml);
        $cert->assessment = assessment::from_edci($cert, $xml);
        $cert->qualification = qualification::from_edci($xml);

        $cert->agents[] = $cert->issuer;

        $cert->credentialsubject = credential_subject::from_edci($cert, $xml);

        if (isset($xml->image)) {
            $cert->image = image::from_edci($xml->image);
        }
        if (isset($xml->assertionPage)) {
            $cert->assertionpage = (string) $xml->assertionPage;
        }

        $cert->signature = signature::from_edci($xml);
        $cert->contract = contract::from_edci($xml);
        $cert->verification = verification::from_edci($xml);

        if (isset($xml->institutionToken)) {
            $cert->institutiontoken = (string) $xml->institutionToken;
        }

        if (isset($xml->tags)) {
            foreach ($xml->tags as $tag) {
                if (!empty($tag)) {
                    $cert->tags[] = (string) $tag->text;
                }
            }
        }

        if (isset($xml->criteria)) {
            $cert->criteria = (string) $xml->criteria->text;
        }

        return $cert;
    }

    /**
     * Creates a certificate object based on an openBadge certificate.
     *
     * @param string $json_string Contains blockshain certificate information in openBadge format.
     * @return certificate
     */
    public static function from_ob($jsonstring) {
        $cert = new certificate();
        $data = json_decode($jsonstring);
        $cert->identifier = manager::get_if_key_exists($data, 'id');
        $cert->issuedon = manager::get_if_key_exists($data, 'issuedOn');
        $cert->validuntil = manager::get_if_key_exists($data, 'expires');
        $cert->title = $data->badge->name;
        $cert->description = manager::get_if_key_exists($data->badge, 'description');
        $cert->issuer = organization::from_ob($data);
        $cert->assessment = assessment::from_ob($cert, $data);
        $cert->qualification = qualification::from_ob();

        $cert->agents[] = $cert->issuer;

        $cert->credentialsubject = credential_subject::from_ob($cert, $data);

        if (isset($data->badge->image)) {
            $cert->image = image::from_ob('image', $data->badge->image);
        }
        if (isset($data->badge->tags)) {
            $cert->tags = $data->badge->tags;
        }
        if (isset($data->badge->criteria)) {
            $cert->criteria = $data->badge->criteria;
        }

        if (isset($data->{'extensions:assertionpageB4E'})) {
            $cert->assertionpage = $data->{'extensions:assertionpageB4E'}->assertionpage;
        }
        $cert->signature = signature::from_ob($data);
        $cert->contract = contract::from_ob($data);
        $cert->verification = verification::from_ob($data);

        if (isset($data->{'extensions:institutionTokenILD'})) {
            $cert->institutiontoken = $data->{'extensions:institutionTokenILD'}->institutionToken;
        }

        return $cert;
    }

    /**
     * Returns a json string containing certificate data in openBadge data.
     * Alternativly returns an the json data as an object, when $as_string is false.
     *
     * @param string $as_string Controls the return type.
     * @return string|\stdClass
     */
    public function get_ob($asstring = true) {
        $ob = new \stdClass();
        $ob->badge = $this->get_badge();
        $ob->{'extensions:examinationRegulationsB4E'} = $this->assessment->get_spec()->get_ob();
        $ob->{'@context'} = \mod_ilddigitalcert\bcert\manager::CONTEXT_OPENBADGES;
        $ob->recipient = (object) ['type' => 'email', 'hashed' => false];
        $ob->{'extensions:recipientB4E'} = $this->credentialsubject->get_ob();
        $ob->{'extensions:examinationB4E'} = $this->assessment->get_ob();
        $ob->type = 'Assertion';

        $ob->id = $this->identifier;
        $ob->issuedOn = $this->issuedon;
        if (isset($this->validuntil)) {
            $ob->expires = $this->validuntil;
        }
        if (isset($this->identifier)) {
            $ob->{'extensions:assertionreferenceB4E'} = (object) [
                'assertionreference' => $this->identifier,
                '@context' => \mod_ilddigitalcert\bcert\manager::CONTEXT_B4E_ASSERTIONREFERENCE,
                'type' => ["Extension", "AssertionReferenceB4E"]
            ];
        }
        if (isset($this->assertionpage)) {
            $ob->{'extensions:assertionpageB4E'} = (object) [
                '@context' => \mod_ilddigitalcert\bcert\manager::CONTEXT_B4E_ASSERTIONPAGE,
                'type' => ["Extension", "AssertionPageB4E"],
                'assertionpage' => $this->assertionpage
            ];
        }

        if (isset($this->signature)) {
            $ob->{'extensions:signatureB4E'} = $this->signature->get_ob();
        }
        if (isset($this->contract)) {
            $ob->{'extensions:contractB4E'} = $this->contract->get_ob();
        }
        if (isset($this->verification)) {
            $ob->{'verification'}  = $this->verification->get_ob();
        }
        if (isset($this->institutiontoken)) {
            $ob->{'extensions:institutionTokenILD'} = (object) [
                '@context' => \mod_ilddigitalcert\bcert\manager::CONTEXT_ILD_INSTITUTION_TOKEN,
                'type' => ["Extension", "InstitutionTokenILD"],
                'institutionToken' => $this->institutiontoken
            ];
        }

        if (!$asstring) {
            return $ob;
        }
        return json_encode($ob, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Creates an default object containing info about the badge in openBadge format.
     *
     * @return \stdClass
     */
    public function get_badge() {
        $badge = new \stdClass();

        if (isset($this->description)) {
            $badge->description = $this->description;
        }
        $badge->name = $this->title;
        $badge->{'extensions:badgeexpertiseB4E'} = $this->credentialsubject->get_expertise();
        $badge->issuer = $this->issuer->get_ob();
        $badge->{'@context'} = \mod_ilddigitalcert\bcert\manager::CONTEXT_OPENBADGES;
        $badge->type = 'BadgeClass';
        $badge->{'extensions:badgetemplateB4E'} = (object) [
            '@context' => \mod_ilddigitalcert\bcert\manager::CONTEXT_B4E_BADGETEMPLATE,
            'type' => ["Extension", "BadgeTemplateB4E"]
        ];

        if (isset($this->tags)) {
            $badge->tags = $this->tags;
        }
        if (isset($this->criteria)) {
            $badge->criteria = $this->criteria;
        }

        if (isset($this->image)) {
            $badge->image = $this->image->get_ob();
        }
        return $badge;
    }

    /**
     * Returns a string containing certificate data in edci format.
     * Alternativly retuns the same data as a mySimpleXMLElement if $as_string is false.
     *
     * @param string $as_string Controls the return type.
     * @return string|mySimpleXMLElement
     */
    public function get_edci($asstring = true) {
        $rootnode = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
                    <europassCredential
                        xmlns="http://data.europa.eu/snb"
                        xmlns:cred="http://data.europa.eu/europass/model/credentials/w3c#"
                        xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsdVersion="0.10.0"
                        xsi:schemaLocation="http://data.europa.eu/snb ' .
            'https://raw.githubusercontent.com/ild-thl/edci-schema-extensions/main/bc_edci_credential.xsd">
                    </europassCredential>';
        $root = new mySimpleXMLElement($rootnode);
        $root->addAttribute('cred:id', $this->identifier, self::CRED_NAMESPACE);
        if (isset($this->identifier)) {
            $root->addChild('identifier', manager::xml_escape($this->identifier));
        }
        $root->appendXML(new mySimpleXMLElement(
            '<type targetFrameworkUrl="http://data.europa.eu/snb/credential/25831c2"
                uri="http://data.europa.eu/snb/credential/e34929035b">
                <targetName>
                    <text content-type="text/plain" lang="en">Generic</text>
                </targetName>
            </type>'
        ));
        if (isset($this->signature)) {
            $root->addChild('cred:validFrom', $this->signature->get_certificationdate(), self::CRED_NAMESPACE);
        }
        if (isset($this->issuedon)) {
            $root->addChild('cred:issued', $this->issuedon, self::CRED_NAMESPACE);
        }
        if (isset($this->validuntil)) {
            $root->addChild('cred:validUntil', $this->validuntil, self::CRED_NAMESPACE);
        }
        $issuer = $root->addChild('cred:issuer', '', self::CRED_NAMESPACE);
        $issuer->addAttribute('idref', $this->issuer->get_id());
        $root->addtextnode('title', $this->title);

        if (isset($this->description)) {
            $root->addtextnode('description', $this->description);
        }

        $root->appendXML($this->credentialsubject->get_edci());

        $learnspecrefsnode = $root->addChild('learningSpecificationReferences');
        $learnspecrefsnode->appendXML($this->qualification->get_edci());

        $assessmentspecrefsnode = $root->addChild('assessmentSpecificationReferences');
        $assessmentspecrefsnode->appendXML($this->assessment->get_spec()->get_edci());

        $agentrefsnode = $root->addChild('agentReferences');
        $agentrefsnode->appendXML($this->issuer->get_edci());

        if (isset($this->image)) {
            $root->appendXML($this->image->get_edci());
        }
        if (isset($this->assertionpage)) {
            $root->addChild('assertionPage', $this->assertionpage);
        }
        if (isset($this->signature)) {
            $root->appendXML($this->signature->get_edci());
        }
        if (isset($this->contract)) {
            $root->appendXML($this->contract->get_edci());
        }
        if (isset($this->verification)) {
            $root->appendXML($this->verification->get_edci());
        }
        if (isset($this->institutiontoken)) {
            $root->addChild('institutionToken', $this->institutiontoken);
        }

        if (isset($this->tags)) {
            $tags = mySimpleXMLElement::create_empty('tags');
            foreach ($this->tags as $tag) {
                $tags->addtextnode('tag', $tag);
            }
            $root->addChild('tags', $tags);
        }

        if (isset($this->criteria)) {
            $root->addtextnode('criteria', $this->criteria);
        }

        if (!$asstring) {
            return $root;
        }
        return $root->asXML();
    }

    /**
     * Add data that describes the issuance of the certificate.
     *
     * @param \stdClass $cm Course module
     * @param int $issuedid Id of the issued certificate db record.
     * @param int $issuedon Datetime when the certificate was issued.
     * @return void
     */
    public function issue($cm, $issuedid, $issuedon) {
        $this->identifier = (new \moodle_url('/mod/ilddigitalcert/view.php', array('issuedid' => $issuedid)))->out();
        $this->issuedon = date('c', $issuedon);
        $this->assertionpage = base64_encode(get_certificatehtml($cm->instance, $this->get_ob()));
    }

    /**
     * Add signature and contract info.
     *
     * @param \core_user $certifier
     * @return void
     */
    public function sign($certifier, $courseid) {
        $this->signature = signature::new($certifier, $courseid);
        $this->contract = contract::new();
    }

    /**
     * Adds verification info.
     *
     * @return void
     */
    public function add_verification($hash) {
        $this->verification = verification::new($hash);
    }

    /**
     * Creates a hash of the openBadge version of the certificate.
     * A salt is temporarily added to the certificate before hashing.
     *
     * @param string $salt
     * @return string
     */
    public function get_ob_hash($salt) {
        $previous = $this->institutiontoken;
        $this->institutiontoken = $salt;
        $hash = calculate_hash($this->get_ob());
        $this->institutiontoken = $previous;
        return $hash;
    }

    /**
     * Creates a hash of the edci version of the certificate.
     * A salt is temporarily added to the certificate before hashing.
     *
     * @param string $salt
     * @return string
     */
    public function get_edci_hash($salt) {
        $previous = $this->institutiontoken;
        $this->institutiontoken = $salt;
        $hash = calculate_hash($this->get_edci());
        $this->institutiontoken = $previous;
        return $hash;
    }

    /**
     * Sets the $institutiontoken with value $salt.
     *
     * @param string $salt Salt/Institution token used in verification process,
     * unique to the issuing institution.
     */
    public function add_institutiontoken($salt) {
        $this->institutiontoken = $salt;
    }
}
