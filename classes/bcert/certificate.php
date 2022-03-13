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
class certificate
{
    /**
     * @var string XML root node of an edci certificate, containing info about namespaces and encoding and version.
     */
    const ROOT_STRING = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
    <europassCredential xmlns="http://data.europa.eu/snb"
        xmlns:cred="http://data.europa.eu/europass/model/credentials/w3c#"
        xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        cred:id="urn:credential:631fc04f-0c6b-4f3f-96b2-39bf81a186a5"
        xsdVersion="0.10.0"
        xsi:schemaLocation="http://data.europa.eu/snb https://data.europa.eu/snb/resource/distribution/v1/xsd/schema/genericschema.xsd">
    </europassCredential>';

    /**
     * @var string XML node describing the credential type.
     */
    const EC_TYPE = '<type targetFrameworkUrl="http://data.europa.eu/snb/credential/25831c2" uri="http://data.europa.eu/snb/credential/e34929035b">
                    <targetName>
                        <text content-type="text/plain" lang="en">Generic</text>
                    </targetName>
                </type>';

    /**
     * @var string w3c verifiable credentials namespace.
     */
    const CRED_NAMESPACE = 'http://data.europa.eu/europass/model/credentials/w3c#';

    /**
     * @var string Uri for openBadge json context.
     */
    const CONTEXT_URL = 'https://w3id.org/openbadges/v2';

    /**
     * @var string Datetime the certificate was issued.
     */
    private $issued_on = "";

    /**
     * @var string Title.
     */
    private $title = "";

    /**
     * @var string Description.
     */
    private $description = "";

    /**
     * @var credentialSubject The credential subject or holder.
     */
    private $credential_subject;

    /**
     * @var image image that is used in pdf representation.
     */
    private $image;

    /**
     * @var string base64 encoded pdf content.
     */
    private $assertion_page = "";

    /**
     * @var signature signature of the certifier.
     */
    private $signature;

    /**
     * @var contract Smart contract.
     */
    private $contract;

    /**
     * @var verification object containing data needed for verifying validity.
     */
    private $verification;

    /**
     * @var string A token unique to the issuing institution.
     */
    private $institution_token = "";

    /**
     * @var array An array of organizations involved in the issuing process.
     */
    private $agents = [];

    /**
     * @var Issuer The issuing institution.
     */
    private $issuer;

    /**
     * @var assessment assessment that is rewarded with this certificate.
     */
    private $assessment;

    /**
     * @var qualification qualification info about the subject.
     */
    private $qualification;

    /**
     * @var array An openBadge requirement, that is cuurently left empty.
     */
    private $tags = [];

    /**
     * @var string An openBadge requirement, that is cuurently left empty.
     */
    private $criteria = "";

    /**
     * Constructor.
     */
    private function __construct()
    {
    }

    /**
     * Creates a certificate Object based on an edci certificate.
     *
     * @param string $xml_string Contains blockchain certificate information in edci format.
     * @return certificate
     */
    public static function from_edci($xml_string)
    {
        $xml = new mySimpleXMLElement($xml_string);

        // echo ($xml);
        $bcert = new certificate();
        $bcert->identifier = (string)  $xml->identifier;
        $bcert->issued_on = (string)  $xml->children(certificate::CRED_NAMESPACE)->issued;
        $bcert->title = (string) $xml->title->text;
        $bcert->description = (string) $xml->description->text;
        $bcert->issuer = organization::from_edci($xml);
        $bcert->assessment = assessment::from_edci($xml);
        $bcert->qualification = qualification::from_edci($xml);

        array_push($bcert->agents, $bcert->issuer);

        $bcert->credential_subject = credentialSubject::from_edci($bcert, $xml);
        $bcert->image = image::from_edci($xml->image);
        $bcert->assertion_page = (string)  $xml->assertionPage;
        $bcert->signature = signature::from_edci($xml);
        $bcert->contract = contract::from_edci($xml);
        $bcert->verification = verification::from_edci($xml);

        if (isset($xml->institutionToken)) {
            $bcert->institution_token = (string)  $xml->institutionToken;
        }

        return $bcert;
    }

    /**
     * Creates a certificate Object based on an openBadge certificate.
     *
     * @param string $json_string Contains blockshain certificate information in openBadge format.
     * @return certificate
     */
    public static function from_ob($json_string)
    {
        $bcert = new certificate();
        $data = json_decode($json_string);
        $bcert->identifier = $data->id;
        $bcert->issued_on = $data->issuedOn;
        $bcert->title = $data->badge->name;
        $bcert->description = $data->badge->description;
        $bcert->issuer = organization::from_ob($data);
        $bcert->assessment = assessment::from_ob($bcert, $data);
        $bcert->qualification = qualification::from_ob($data);

        array_push($bcert->agents, $bcert->issuer);

        $bcert->credential_subject = credentialSubject::from_ob($bcert, $data);
        $bcert->image = image::from_ob('image', $data->badge->image);
        $bcert->assertion_page = $data->{'extensions:assertionpageB4E'}->assertionpage;
        $bcert->signature = signature::from_ob($data);
        $bcert->contract = contract::from_ob($data);
        $bcert->verification = verification::from_ob($data);

        if (isset($data->{'extensions:institutionTokenILD'})) {
            $bcert->institution_token = $data->{'extensions:institutionTokenILD'}->institutionToken;
        }

        return $bcert;
    }

    /**
     * Returns the issuing organization.
     *
     * @return organization
     */
    public function get_issuer()
    {
        return $this->issuer;
    }

    /**
     * Returns the assessment.
     *
     * @return assessment
     */
    public function get_assessment()
    {
        return $this->assessment;
    }

    /**
     * Returns the qualification.
     *
     * @return qualification
     */
    public function get_qualification()
    {
        return $this->qualification;
    }

    /**
     * Returns a json string containing certificate data in openBadge data.
     * Alternativly returns an the json data as an object, when $as_string is false.
     *
     * @param string $as_string Controls the return type.
     * @return string||stdClass
     */
    public function get_ob($as_string = true)
    {
        $ob = new \stdClass();
        $ob->badge = $this->get_badge();
        $ob->{'extensions:examinationRegulationsB4E'} = $this->assessment->get_spec()->get_ob();
        $ob->{'@context'} =  certificate::CONTEXT_URL;
        $ob->recipient = (object) ['type' => 'email', 'hashed' => false];
        $ob->{'extensions:recipientB4E'} = $this->credential_subject->get_ob();
        $ob->{'extensions:examinationB4E'} = $this->assessment->get_ob();
        $ob->type = 'Assertion';
        $ob->id = $this->identifier;
        $ob->issuedOn = $this->issued_on;
        $ob->{'extensions:assertionreferenceB4E'} = (object) [
            'assertionreference' => $this->identifier,
            '@context' => 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/AssertionReferenceB4E/context.json',
            'type' => ["Extension", "AssertionReferenceB4E"]
        ];
        $ob->{'extensions:assertionpageB4E'} = (object) [
            '@context' => 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/AssertionPageB4E/context.json',
            'type' => ["Extension", "AssertionPageB4E"],
            'assertionpage' => $this->assertion_page
        ];
        $ob->{'extensions:signatureB4E'} = $this->signature->get_ob();
        $ob->{'extensions:contractB4E'} = $this->contract->get_ob();
        $ob->{'verification'}  = $this->verification->get_ob();
        if ($this->institution_token != "") {
            $ob->{'extensions:institutionTokenILD'} = (object) [
                '@context' => 'https://raw.githubusercontent.com/ild-thl/schema_extension_ild/master/institution_token_ild.json',
                'type' => ["Extension", "InstitutionTokenILD"],
                'institutionToken' => $this->institution_token
            ];
        }

        if ($as_string) {
            return json_encode($ob, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        return $ob;
    }

    /**
     * Creates an default object containing info about the badge in openBadge format.
     *
     * @return object
     */
    function get_badge()
    {
        $badge = new \stdClass();
        $badge->description = $this->description;
        $badge->name = $this->title;
        $badge->{'extensions:badgeexpertiseB4E'} = $this->credential_subject->get_expertise();
        $badge->issuer = $this->issuer->get_ob();
        $badge->{'@context'} = certificate::CONTEXT_URL;
        $badge->type = 'BadgeClass';
        $badge->{'extensions:badgetemplateB4E'} = (object) [
            '@context' => 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/BadgeTemplateB4E/context.json',
            'type' => ["Extension", "BadgeTemplateB4E"]
        ];
        $badge->tags = $this->tags;
        $badge->criteria = $this->criteria;
        $badge->image = $this->image->get_ob();
        return $badge;
    }

    /**
     * Returns a string containing certificate data in edci format.
     * Alternativly retuns the same data as a mySimpleXMLElement if $as_string is false.
     *
     * @param string $as_string Controls the return type.
     * @return string||mySimpleXMLElement
     */
    public function get_edci($as_string = true)
    {
        $root = new mySimpleXMLElement(certificate::ROOT_STRING);
        $root->addChild('identifier', manager::xml_escape($this->identifier));
        $root->appendXML(new mySimpleXMLElement(certificate::EC_TYPE));
        $root->addChild('cred:issued', $this->issued_on, certificate::CRED_NAMESPACE);
        $issuer = $root->addChild('cred:issuer', '', certificate::CRED_NAMESPACE);
        $issuer->addAttribute('idref', $this->issuer->get_id());
        $root->addTextNode('title', $this->title);
        $root->addTextNode('description', $this->description);

        $root->appendXML($this->credential_subject->get_edci());

        $learn_spec_refs_node = $root->addChild('learningSpecificationReferences');
        $learn_spec_refs_node->appendXML($this->qualification->get_edci());

        $assessment_spec_refs_node = $root->addChild('assessmentSpecificationReferences');
        $assessment_spec_refs_node->appendXML($this->assessment->get_spec()->get_edci());

        $agent_refs_node = $root->addChild('agentReferences');
        $agent_refs_node->appendXML($this->issuer->get_edci());

        if($this->image) {
            $root->appendXML($this->image->get_edci());
        }

        $root->addChild('assertionPage', $this->assertion_page);

        $root->appendXML($this->signature->get_edci());

        $root->appendXML($this->contract->get_edci());

        $root->appendXML($this->verification->get_edci());

        $root->addChild('institutionToken', $this->institution_token);

        if ($as_string) {
            return $root->asXML();
        }
        return $root;
    }

    /**
     * Sets the $institution_token with value $salt.
     *
     * @param string $salt Salt/Institution token used in verification process,
     * unique to the issuing institution.
     */
    function add_institution_token($salt)
    {
        $this->institution_token = $salt;
    }
}
