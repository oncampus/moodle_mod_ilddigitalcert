<?php
class MySimpleXMLElement extends SimpleXMLElement
{
    public static function create_empty(string $name)
    {
        return new static("<$name/>");
    }

    /**
     * Add SimpleXMLElement code into a SimpleXMLElement
     *
     * @param MySimpleXMLElement $append
     */
    public function appendXML($append)
    {
        if ($append) {
            if (strlen(trim((string)$append)) == 0) {
                $xml = $this->addChild($append->getName());
            } else {
                $xml = $this->addChild($append->getName(), xml_escape((string)$append));
            }

            foreach ($append->children() as $child) {
                $xml->appendXML($child);
            }

            foreach ($append->attributes() as $n => $v) {
                $xml->addAttribute($n, $v);
            }
        }
    }

    /**
     * Add SimpleXMLElement code into a SimpleXMLElement
     *
     * @param MySimpleXMLElement $append
     */
    public function addTextNode($name, $content, $language = 'de')
    {
        $child = $this->addChild($name, '');
        $text = $child->addChild('text', $content);
        $text->addAttribute('content-type', 'text/plain');
        $text->addAttribute('lang', $language);
    }
}

class BCert
{
    const ROOT_STRING = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
    <europassCredential xmlns="http://data.europa.eu/snb"
        xmlns:cred="http://data.europa.eu/europass/model/credentials/w3c#"
        xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        cred:id="urn:credential:631fc04f-0c6b-4f3f-96b2-39bf81a186a5"
        xsdVersion="0.10.0"
        xsi:schemaLocation="http://data.europa.eu/snb https://data.europa.eu/snb/resource/distribution/v1/xsd/schema/genericschema.xsd">
    </europassCredential>';

    const EC_TYPE = '<type targetFrameworkUrl="http://data.europa.eu/snb/credential/25831c2" uri="http://data.europa.eu/snb/credential/e34929035b">
                    <targetName>
                        <text content-type="text/plain" lang="en">Generic</text>
                    </targetName>
                </type>';
    const CRED_NAMESPACE = 'http://data.europa.eu/europass/model/credentials/w3c#';
    const CONTEXT_URL = 'https://w3id.org/openbadges/v2';

    private $issued_on = "";
    private $title = "";
    private $description = "";
    private $credential_subject;
    private $image;
    private $assertion_page = "";
    private $signature;
    private $contract;
    private $verification;
    private $institution_token = "";
    private $agents = [];
    private $issuer;
    private $assessment;
    private $qualification;
    private $tags = [];
    private $criteria = "";

    private function __construct()
    {
    }

    public static function from_edci($xml_string)
    {
        $xml = new MySimpleXMLElement($xml_string);

        $bcert = new BCert();
        $bcert->identifier = (string)  $xml->identifier;
        $bcert->issued_on = (string)  $xml->children(BCert::CRED_NAMESPACE)->issued;
        $bcert->title = (string) $xml->title->text;
        $bcert->description = (string) $xml->description->text;
        $bcert->issuer = Organisation::from_edci($bcert, $xml);
        $bcert->assessment = Assessment::from_edci($bcert, $xml);
        $bcert->qualification = Qualification::from_edci($bcert, $xml);

        array_push($bcert->agents, $bcert->issuer);

        $bcert->credential_subject = CredentialSubject::from_edci($bcert, $xml);
        $bcert->image = Image::from_edci($xml->image);
        $bcert->assertion_page = (string)  $xml->assertionPage;
        $bcert->signature = Signature::from_edci($xml);
        $bcert->contract = Contract::from_edci($xml);
        $bcert->verification = Verification::from_edci($xml);

        if (isset($xml->institutionToken)) {
            $bcert->institution_token = $xml->institutionToken;
        }

        return $bcert;
    }

    public static function from_ob($json_string)
    {
        $bcert = new BCert();
        $data = json_decode($json_string);
        $bcert->identifier = $data->id;
        $bcert->issued_on = $data->issuedOn;
        $bcert->title = $data->badge->name;
        $bcert->description = $data->badge->description;
        $bcert->issuer = Organisation::from_ob($bcert, $data);
        $bcert->assessment = Assessment::from_ob($bcert, $data);
        $bcert->qualification = Qualification::from_ob($bcert, $data);

        array_push($bcert->agents, $bcert->issuer);

        $bcert->credential_subject = CredentialSubject::from_ob($bcert, $data);
        $bcert->image = Image::from_ob('image', $data->badge->image);
        $bcert->assertion_page = $data->{'extensions:assertionpageB4E'}->assertionpage;
        $bcert->signature = Signature::from_ob($data);
        $bcert->contract = Contract::from_ob($data);
        $bcert->verification = Verification::from_ob($data);

        if (isset($data->{'extensions:institutionTokenILD'})) {
            $bcert->institution_token = $data->{'extensions:institutionTokenILD'}->institutionToken;
        }

        return $bcert;
    }

    public function get_issuer()
    {
        return $this->issuer;
    }

    public function get_assessment()
    {
        return $this->assessment;
    }

    public function get_qualification()
    {
        return $this->qualification;
    }

    public function get_ob_cert($as_string = true)
    {
        $ob = new stdClass();
        $ob->badge = $this->get_badge();
        $ob->{'extensions:examinationRegulationsB4E'} = $this->assessment->get_spec()->get_ob();
        $ob->{'@context'} =  BCert::CONTEXT_URL;
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

    function get_badge()
    {
        $badge = new stdClass();
        $badge->description = $this->description;
        $badge->name = $this->title;
        $badge->{'extensions:badgeexpertiseB4E'} = $this->credential_subject->get_expertise();
        $badge->issuer = $this->issuer->get_ob();
        $badge->{'@context'} = BCert::CONTEXT_URL;
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

    public function get_edci_cert($as_string = true)
    {
        $root = new MySimpleXMLElement(BCert::ROOT_STRING);
        $root->addChild('identifier', xml_escape($this->identifier));
        $root->appendXML(new MySimpleXMLElement(BCert::EC_TYPE));
        $root->addChild('cred:issued', $this->issued_on, BCert::CRED_NAMESPACE);
        $issuer = $root->addChild('cred:issuer', '', BCert::CRED_NAMESPACE);
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

        $root->appendXML($this->image->get_edci());

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

    function add_institution_token($salt)
    {
        $this->institution_token = $salt;
    }
}

class CredentialSubject
{
    private $identifier = "";
    private $email = "";
    private $given_names = "";
    private $family_name = "";
    private $achievements = [];


    private function __construct()
    {
    }

    public static function from_edci($bcert, $xml)
    {
        $new = new CredentialSubject();
        $data = $xml->credentialSubject;
        $new->identifier = (string) $data->identifier;
        $new->email = str_replace('mailto:', '', $data->contactPoint->mailBox['uri']);
        $new->given_names = (string) $data->givenNames->text;
        $new->family_name = (string) $data->familyName->text;

        foreach ($data->achievements->learningAchievement as $learningAchievement) {
            array_push($new->achievements, Achievement::from_edci($bcert, $learningAchievement));
        }

        return $new;
    }

    public static function from_ob($bcert, $json)
    {
        $new = new CredentialSubject();
        $new->identifier = $json->{'extensions:recipientB4E'}->reference;
        $new->email = $json->{'extensions:recipientB4E'}->email;
        $new->given_names = $json->{'extensions:recipientB4E'}->givenname;
        $new->family_name = $json->{'extensions:recipientB4E'}->surname;

        $expertises = $json->badge->{'extensions:badgeexpertiseB4E'}->expertise;
        foreach ($expertises as $expertise) {
            array_push($new->achievements, Achievement::from_ob($bcert, $expertise));
        }
        return $new;
    }

    public function get_expertise()
    {
        $expertise = [];

        foreach ($this->achievements as $achievement) {
            array_push($expertise, $achievement->get_expertise());
        }

        return (object) [
            '@context' => 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/BadgeExpertiseB4E/context.json',
            'type' => ["Extension", "BadgeExpertiseB4E"],
            'expertise' => $expertise
        ];
    }

    public function get_ob()
    {
        $recipient = new stdClass();
        $recipient->reference = $this->identifier;
        $recipient->email = $this->email;
        $recipient->{'@context'} = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/RecipientB4E/context.json';
        $recipient->type = ["Extension", "RecipientB4E"];
        $recipient->givenname = $this->given_names;
        $recipient->surname = $this->family_name;
        return $recipient;
    }

    public function get_edci()
    {
        $root = MySimpleXMLElement::create_empty('credentialSubject');
        $root->addAttribute('id', 'urn:bcert:person:1');
        $root->addChild('identifier', $this->identifier);
        $contact_point = $root->addChild('contactPoint');
        $mail_box = $contact_point->addChild('mailBox');
        $mail_box->addAttribute('uri', 'mailto:' . $this->email);
        $root->addTextNode('givenNames', $this->given_names);
        $root->addTextNode('familyName', $this->family_name);

        $achievementsNode = $root->addChild('achievements');
        foreach ($this->achievements as $achievement) {
            $achievementsNode->appendXML($achievement->get_edci());
        }

        return $root;
    }
}

class Assessment
{
    private static $count = 0;

    private $id = "";
    private $title = "";
    private $grade = "bestanden";
    private $startdate;
    private $enddate;
    private $place;
    private $assessed_by = "";
    private $spec;

    public function get_spec()
    {
        return $this->spec;
    }


    private function __construct()
    {
    }

    public static function from_edci($bcert, $xml)
    {
        $assessment = new Assessment();
        $ass_xml = $xml->credentialSubject->achievements->learningAchievement['0']->wasDerivedFrom;
        $assessment->id = $ass_xml['id'];

        $assessment->title = (string) $ass_xml->title->text;

        // TODO: get grade
        $assessment->grade = (string) $ass_xml->grade;
        $assessment->startdate = (string) $ass_xml->startdate;
        $assessment->enddate = (string) $ass_xml->enddate;
        $assessment->place = (string) $ass_xml->place;
        $assessment->assessed_by = $ass_xml->assessedBy['idref'];

        $assessment->spec = AssessmentSpec::from_edci($bcert, $xml);
        return $assessment;
    }

    public static function from_ob($bcert, $json)
    {
        $assessment = new Assessment();
        self::$count += 1;
        $assessment->id = 'urn:bcert:assessment:' . self::$count;
        if (isset($json->{'extensions:examinationB4E'}->title)) {
            $assessment->title = $json->{'extensions:examinationB4E'}->title;
        } else {
            $assessment->title = $json->badge->name;
        }

        // TODO: get grade
        $assessment->startdate = get_if_object_key_exists($json->{'extensions:examinationB4E'}, 'startdate');
        $assessment->enddate = get_if_object_key_exists($json->{'extensions:examinationB4E'}, 'endate');
        $assessment->place = get_if_object_key_exists($json->{'extensions:examinationB4E'}, 'place');
        $assessment->assessed_by = $bcert->get_issuer()->get_id();

        $assessment->spec = AssessmentSpec::from_ob($bcert, $json);
        return $assessment;
    }


    public function get_ob()
    {
        $assessment = new stdClass();
        $assessment->title = $this->title;
        $assessment->startdate = $this->startdate;
        $assessment->enddate = $this->enddate;
        $assessment->place = $this->place;
        $assessment->{'@context'} = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/ExaminationB4E/context.json';
        $assessment->type = ["Extension", "ExaminationB4E"];
        return $assessment;
    }


    public function get_edci()
    {
        $root = MySimpleXMLElement::create_empty('wasDerivedFrom');
        $root->addAttribute('id', $this->id);
        $root->addTextNode('title', $this->title);
        $root->addChild('grade', $this->grade);
        $root->addChild('assessedBy')->addAttribute('idref', $this->assessed_by);
        $root->addChild('specifiedBy')->addAttribute('idref', $this->spec->get_id());
        $root->addChild('startdate', $this->startdate);
        $root->addChild('enddate', $this->enddate);
        $root->addChild('place', $this->place);

        return $root;
    }
}

class AssessmentSpec
{
    private static $count = 0;
    private $id = "";
    private $title = "";
    private $identifier = "";
    private $homepage = "";
    private $date = "";

    public function get_id()
    {
        return $this->id;
    }


    private function __construct()
    {
    }

    public static function from_edci($bcert, $xml)
    {
        $new = new AssessmentSpec();
        $spec_xml = $xml->assessmentSpecificationReferences->assessmentSpecification;
        $new->id = $spec_xml['id'];
        $new->title = (string) $spec_xml->title->text;
        $new->homepage = (string) $spec_xml->homepage['uri'];
        $new->identifier = (string) $spec_xml->identifier;
        $new->date = (string) $spec_xml->date;
        return $new;
    }

    public static function from_ob($bcert, $json)
    {
        $new = new AssessmentSpec();
        self::$count += 1;
        $new->id = 'urn:bcert:asssessmentspec:' . self::$count;
        $new->title = $json->{'extensions:examinationRegulationsB4E'}->title;
        $new->homepage = $json->{'extensions:examinationRegulationsB4E'}->url;
        $new->identifier = $json->{'extensions:examinationRegulationsB4E'}->regulationsid;
        $new->date = get_if_object_key_exists($json->{'extensions:examinationRegulationsB4E'}, 'date');
        return $new;
    }


    public function get_ob()
    {
        $spec = new stdClass();
        $spec->title = $this->title;
        $spec->regulationsid = $this->identifier;
        $spec->url = $this->homepage;
        $spec->date = $this->date;
        $spec->{'@context'} = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/ExaminationRegulationsB4E/context.json';
        $spec->type = ["Extension", "ExaminationRegulationsB4E"];
        return $spec;
    }

    public function get_edci()
    {
        $root = MySimpleXMLElement::create_empty('assessmentSpecification');
        $root->addAttribute('id', $this->id);
        $root->addChild('identifier', $this->identifier);
        $root->addTextNode('title', $this->title);
        $root->addChild('homepage')->addAttribute('uri', $this->homepage);
        $root->addChild('date', $this->date);

        return $root;
    }
}


class Achievement
{
    private static $count = 0;
    private $id = "";
    private $title = "";
    private $assessment;
    private $qualification;

    public function get_expertise()
    {
        return $this->title;
    }


    private function __construct()
    {
    }

    public static function from_edci($bcert, $xml)
    {
        $new = new Achievement();
        $new->id = $xml['id'];
        $new->title = (string) $xml->title->text;
        $new->assessment = $bcert->get_assessment();
        $new->qualification = $bcert->get_qualification();
        return $new;
    }

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

class Qualification
{
    const QUAL_TYPE = '<type targetFrameworkUrl="http://data.europa.eu/snb/learning-opportunity/25831c2" uri="http://data.europa.eu/snb/learning-opportunity/05053c1cbe">
                            <targetName>
                                <text content-type="text/plain" lang="en">Course</text>
                            </targetName>
                        </type>';
    const QUAL_TITLE = '<title>
                            <text content-type="text/plain" lang="en">Joint achievement</text>
                        </title>';

    private static $count = 0;
    private $id = "";
    // private $ects;

    public function get_id()
    {
        return $this->id;
    }


    private function __construct()
    {
    }

    public static function from_edci($bcert, $xml)
    {
        $new = new Qualification();
        $new->id = $xml->learningSpecificationReferences->qualification['id'];
        // $this->ects = $data->->ects;
        return $new;
    }

    public static function from_ob($bcert, $json)
    {
        $new = new Qualification();
        self::$count += 1;
        $new->id = 'urn:bcert:qualification:' . self::$count;
        // $this->ects = $data->->ects;
        return $new;
    }

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

class Organisation
{
    private static $count = 0;
    private $id = "";
    private $identifier = "";
    private $registration = "DUMMY-REGISTRATION";
    private $pref_label = "";
    private $alt_label = "";
    private $homepage = "";
    private $location = "";
    private $zip = "";
    private $street = "";
    private $full_address = "";
    private $logo;
    private $email = "";

    public function get_id()
    {
        return $this->id;
    }

    private function __construct()
    {
    }

    public static function from_edci($bcert, $xml)
    {
        $org = new Organisation();
        $org_xml = $xml->agentReferences->organization[0];
        $org->id = $org_xml['id'];
        $org->identifier = (string) $org_xml->identifier;
        $org->pref_label = (string) $org_xml->prefLabel->text;
        $org->alt_label = (string) $org_xml->altLabel->text;
        $org->email = (string) $org_xml->email;
        $org->homepage = (string) $org_xml->homepage['uri'];
        $org->location = (string) $org_xml->hasLocation->hasAddress->location;
        $org->zip = (string) $org_xml->hasLocation->hasAddress->zip;
        $org->street = (string) $org_xml->hasLocation->hasAddress->street;
        $org->full_address = $org_xml->hasLocation->hasAddress->fullAddress->text;
        $org->logo = Image::from_edci($org_xml->logo);
        return $org;
    }

    public static function from_ob($bcert, $data)
    {
        $org = new Organisation();
        self::$count += 1;
        $org->id = 'urn:bcert:org:' . self::$count;
        $org->identifier = $data->badge->issuer->id;
        $org->pref_label = $data->badge->issuer->name;
        $org->alt_label = $data->badge->issuer->description;
        $org->email = $data->badge->issuer->email;
        $org->homepage = $data->badge->issuer->url;
        $org->location = $data->badge->issuer->{'extensions:addressB4E'}->location;
        $org->zip = $data->badge->issuer->{'extensions:addressB4E'}->zip;
        $org->street = $data->badge->issuer->{'extensions:addressB4E'}->street;
        $org->full_address = $org->street . ', ' . $org->zip . ' ' . $org->location;
        $org->logo = Image::from_ob('logo', $data->badge->issuer->image);
        return $org;
    }

    public function get_ob()
    {
        $issuer = new stdClass();
        $issuer->description = $this->alt_label;
        $issuer->{'extensions:addressB4E'} = (object) [
            'location' => $this->location,
            'zip' => $this->zip,
            'street' => $this->street,
            '@context' => 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/AddressB4E/context.json',
            'type' => ["Extension", "AddressB4E"]
        ];
        $issuer->email = $this->email;
        $issuer->name = $this->pref_label;
        $issuer->url = $this->homepage;
        $issuer->{'@context'} = 'https://w3id.org/openbadges/v2';
        $issuer->type = 'Issuer';
        $issuer->id = $this->identifier;
        $issuer->image = $this->logo->get_ob();

        return $issuer;
    }

    public function get_edci()
    {
        $root = MySimpleXMLElement::create_empty('organization');
        $root->addAttribute('id', $this->id);
        $root->addChild('identifier', xml_escape($this->identifier));
        $root->addChild('registration', $this->registration);
        $root->addTextNode('prefLabel', $this->pref_label);
        $root->addTextNode('altLabel', $this->alt_label);
        $root->addChild('homepage')->addAttribute('uri', $this->homepage);
        $has_location = $root->addChild('hasLocation');
        $has_address = $has_location->addChild('hasAddress');
        $has_address->addTextNode('fullAddress', $this->full_address);
        $has_address->addChild('location', $this->location);
        $has_address->addChild('zip', $this->zip);
        $has_address->addChild('street', $this->street);

        $root->appendXML($this->logo->get_edci());

        $root->addChild('email', $this->email);


        // $root->addChild('hasECTSCreditPoints', $this->ects);

        return $root;
    }
}

class Image
{

    private $name = "";
    private $type_uri = "http://publications.europa.eu/resource/authority/file-type/";
    private $type_name = "";
    private $content = "";

    private function __construct()
    {
    }

    public static function from_edci($xml)
    {
        $image = new Image();

        $image->name = $xml->getName();
        $image->type_uri = $xml->contentType['uri'];
        $image->type_name = $xml->contentType->targetName->text;
        $image->content = $xml->content;

        return $image;
    }

    public static function from_ob($name, $string)
    {
        $image = new Image();

        $matchs = [];
        preg_match("/data:image\/(.*);base64,(.*)/", $string, $matchs);

        $image->name = $name;
        $image->type_name = strtoupper($matchs[1]);
        $image->type_uri .= $image->type_name;
        $image->content .= $matchs[2];
        return $image;
    }


    public function get_ob()
    {
        return 'data:image/' . strtolower($this->type_name) . ';base64,' . $this->content;
    }


    public function get_edci()
    {
        $root = MySimpleXMLElement::create_empty($this->name);

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

class Signature
{

    private $address = "";
    private $email = "";
    private $givenname = "";
    private $surname = "";
    private $role = "";
    private $certificationdate = "";

    private function __construct()
    {
    }

    public static function from_edci($xml)
    {
        $new = new Signature();
        $new->address = (string) $xml->signature->address;
        $new->email = (string)  $xml->signature->email;
        $new->givenname = (string) $xml->signature->givenname;
        $new->surname = (string) $xml->signature->surname;
        $new->role = (string) $xml->signature->role;
        $new->certificationdate = (string) $xml->signature->certificationdate;
        return $new;
    }

    public static function from_ob($json)
    {
        $new = new Signature();
        $new->address = $json->{'extensions:signatureB4E'}->address;
        $new->email = $json->{'extensions:signatureB4E'}->email;
        $new->givenname = $json->{'extensions:signatureB4E'}->givenname;
        $new->surname = $json->{'extensions:signatureB4E'}->surname;
        $new->role = $json->{'extensions:signatureB4E'}->role;
        $new->certificationdate = $json->{'extensions:signatureB4E'}->certificationdate;
        return $new;
    }


    public function get_ob()
    {
        $signature = new stdClass();
        $signature->address = $this->address;
        $signature->email = $this->email;
        $signature->surname = $this->surname;
        $signature->{'@context'} = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/SignatureB4E/context.json';
        $signature->role = $this->role;
        $signature->certificationdate = $this->certificationdate;
        $signature->type = ["Extension", "SignatureB4E"];
        $signature->givenname = $this->givenname;
        return $signature;
    }


    public function get_edci()
    {
        $root = MySimpleXMLElement::create_empty('signature');

        $root->addChild('address', $this->address);
        $root->addChild('email', $this->email);
        $root->addChild('givenname', $this->givenname);
        $root->addChild('surname', $this->surname);
        $root->addChild('role', $this->role);
        $root->addChild('certificationdate', $this->certificationdate);

        return $root;
    }
}

class Contract
{

    private $abi = "";
    private $address = "";
    private $node = "";

    private function __construct()
    {
    }

    public static function from_edci($xml)
    {
        $new = new Contract();
        $new->abi = (string) $xml->contract->abi;
        $new->address = (string) $xml->contract->address;
        $new->node = (string) $xml->contract->node;
        return $new;
    }

    public static function from_ob($json)
    {
        $new = new Contract();
        $new->abi = $json->{'extensions:contractB4E'}->abi;
        $new->address = $json->{'extensions:contractB4E'}->address;
        $new->node = $json->{'extensions:contractB4E'}->node;
        return $new;
    }


    public function get_ob()
    {
        $cobtract = new stdClass();
        $cobtract->{'@context'} = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/ContractB4E/context.json';
        $cobtract->type = ["Extension", "ContractB4E"];
        $cobtract->abi = $this->abi;
        $cobtract->address = $this->address;
        $cobtract->node = $this->node;
        return $cobtract;
    }


    public function get_edci()
    {
        $root = MySimpleXMLElement::create_empty('contract');

        $root->addChild('abi', xml_escape($this->abi));
        $root->addChild('address', xml_escape($this->address));
        $root->addChild('node', xml_escape($this->node));

        return $root;
    }
}

class Verification
{

    private $verifyaddress = "";
    private $assertionhash = "";

    private function __construct()
    {
    }

    public static function from_edci($xml)
    {
        $new = new Verification();
        $new->verifyaddress = (string) $xml->verification->verifyaddress;
        $new->assertionhash = (string) $xml->verification->assertionhash;
        return $new;
    }

    public static function from_ob($json)
    {
        $new = new Verification();
        $new->verifyaddress = $json->verification->{'extensions:verifyB4E'}->verifyaddress;
        $new->assertionhash = $json->verification->{'extensions:verifyB4E'}->assertionhash;
        return $new;
    }


    public function get_ob()
    {
        $verification = new stdClass();
        $verification->{'extensions:verifyB4E'} = new stdClass();
        $verification->{'extensions:verifyB4E'}->verifyaddress = $this->verifyaddress;
        $verification->{'extensions:verifyB4E'}->type = ["Extension", "VerifyB4E"];
        $verification->{'extensions:verifyB4E'}->assertionhash = $this->assertionhash;
        $verification->{'extensions:verifyB4E'}->{'@context'} = 'https://perszert.fit.fraunhofer.de/publicSchemaB4E/VerifyB4E/context.json';
        return $verification;
    }


    public function get_edci()
    {
        $root = MySimpleXMLElement::create_empty('verification');

        $root->addChild('verifyaddress', xml_escape($this->verifyaddress));
        $root->addChild('assertionhash', xml_escape($this->assertionhash));

        return $root;
    }
}

function get_if_object_key_exists($object, $key)
{
    if (isset($object->{$key})) {
        return $object->$key;
    } else {
        return null;
    }
}


function get_if_array_key_exists($array, $key)
{
    if (array_key_exists($key, $array)) {
        return $array[$key];
    } else {
        return null;
    }
}

function xml_escape($string)
{
    return str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $string);
}
