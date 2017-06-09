<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Data;

class DataTest extends BaseUnitTestCase
{
    private $body;
    private $data;

    public function setUp()
    {
        $this->body = m::mock("Rollbar\Payload\Body");
        $this->data = new Data("test", $this->body);
    }

    public function testEnvironmentMustBeString()
    {
        try {
            $data = new Data(1, $this->body);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertContains("must be a string", $e->getMessage());
        }

        try {
            $data = new Data(null, $this->body);
            $this->fail("Above should throw");
        } catch (\InvalidArgumentException $e) {
            $this->assertContains("must not be null", $e->getMessage());
        }

        $data = new Data("env", $this->body);
        $this->assertEquals("env", $data->getEnvironment());

        $this->assertEquals("test", $data->setEnvironment("test")->getEnvironment());
    }

    public function testBody()
    {
        $data = new Data("env", $this->body);
        $this->assertEquals($this->body, $data->getBody());

        $body2 = m::mock("Rollbar\Payload\Body");
        $this->assertEquals($body2, $data->setBody($body2)->getBody());
    }

    public function testLevel()
    {
        $level = m::mock("Rollbar\Payload\Level");
        $this->assertEquals($level, $this->data->setLevel($level)->getLevel());
    }

    public function testTimestamp()
    {
        $timestamp = time();
        $this->assertEquals($timestamp, $this->data->setTimestamp($timestamp)->getTimestamp());
    }

    public function testCodeVersion()
    {
        $codeVersion = "v0.18.1";
        $this->assertEquals($codeVersion, $this->data->setCodeVersion($codeVersion)->getCodeVersion());
    }

    public function testPlatform()
    {
        $platform = "Linux";
        $this->assertEquals($platform, $this->data->setPlatform($platform)->getPlatform());
    }

    public function testLanguage()
    {
        $language = "PHP";
        $this->assertEquals($language, $this->data->setLanguage($language)->getLanguage());
    }

    public function testFramework()
    {
        $framework = "Laravel";
        $this->assertEquals($framework, $this->data->setFramework($framework)->getFramework());
    }

    public function testContext()
    {
        $context = "SuperController->getResource()";
        $this->assertEquals($context, $this->data->setContext($context)->getContext());
    }

    public function testRequest()
    {
        $request = m::mock("Rollbar\Payload\Request");
        $this->assertEquals($request, $this->data->setRequest($request)->getRequest());
    }

    public function testPerson()
    {
        $person = m::mock("Rollbar\Payload\Person");
        ;
        $this->assertEquals($person, $this->data->setPerson($person)->getPerson());
    }

    public function testServer()
    {
        $server = m::mock("Rollbar\Payload\Server");
        $this->assertEquals($server, $this->data->setServer($server)->getServer());
    }

    public function testCustom()
    {
        $custom = array(
            "x" => 1,
            "y" => 2,
            "z" => 3,
        );
        $this->assertEquals($custom, $this->data->setCustom($custom)->getCustom());
    }

    public function testFingerprint()
    {
        $fingerprint = "bad-error-with-database";
        $this->assertEquals($fingerprint, $this->data->setFingerprint($fingerprint)->getFingerprint());
    }

    public function testTitle()
    {
        $title = "End of the World as we know it";
        $this->assertEquals($title, $this->data->setTitle($title)->getTitle());
    }

    public function testUuid()
    {
        $uuid = "21EC2020-3AEA-4069-A2DD-08002B30309D";
        $this->assertEquals($uuid, $this->data->setUuid($uuid)->getUuid());
    }

    public function testNotifier()
    {
        $notifier = m::mock("Rollbar\Payload\Notifier");
        $this->assertEquals($notifier, $this->data->setNotifier($notifier)->getNotifier());
    }

    public function testEncode()
    {
        $time = time();
        $level = $this->mockSerialize("Rollbar\Payload\Level", "{LEVEL}");
        $body = $this->mockSerialize($this->body, "{BODY}");
        $request = $this->mockSerialize("Rollbar\Payload\Request", "{REQUEST}");
        $person = $this->mockSerialize("Rollbar\Payload\Person", "{PERSON}");
        $server = $this->mockSerialize("Rollbar\Payload\Server", "{SERVER}");
        $notifier = $this->mockSerialize("Rollbar\Payload\Notifier", "{NOTIFIER}");

        $data = $this->data
            ->setEnvironment("testing")
            ->setBody($body)
            ->setLevel($level)
            ->setTimestamp($time)
            ->setCodeVersion("v0.17.3")
            ->setPlatform("LAMP")
            ->setLanguage("PHP 5.4")
            ->setFramework("CakePHP")
            ->setContext("AppController->updatePerson")
            ->setRequest($request)
            ->setPerson($person)
            ->setServer($server)
            ->setCustom(array("x" => "hello", "extra" => new \ArrayObject()))
            ->setFingerprint("big-fingerprint")
            ->setTitle("The Title")
            ->setUuid("123e4567-e89b-12d3-a456-426655440000")
            ->setNotifier($notifier);

        $encoded = json_encode($data->jsonSerialize());

        $this->assertContains("\"environment\":\"testing\"", $encoded);
        $this->assertContains("\"body\":\"{BODY}\"", $encoded);
        $this->assertContains("\"level\":\"{LEVEL}\"", $encoded);
        $this->assertContains("\"timestamp\":$time", $encoded);
        $this->assertContains("\"code_version\":\"v0.17.3\"", $encoded);
        $this->assertContains("\"platform\":\"LAMP\"", $encoded);
        $this->assertContains("\"language\":\"PHP 5.4\"", $encoded);
        $this->assertContains("\"framework\":\"CakePHP\"", $encoded);
        $this->assertContains("\"context\":\"AppController->updatePerson\"", $encoded);
        $this->assertContains("\"request\":\"{REQUEST}\"", $encoded);
        $this->assertContains("\"person\":\"{PERSON}\"", $encoded);
        $this->assertContains("\"server\":\"{SERVER}\"", $encoded);
        $this->assertContains("\"custom\":{\"x\":\"hello\",\"extra\":{}}", $encoded);
        $this->assertContains("\"fingerprint\":\"big-fingerprint\"", $encoded);
        $this->assertContains("\"title\":\"The Title\"", $encoded);
        $this->assertContains("\"uuid\":\"123e4567-e89b-12d3-a456-426655440000\"", $encoded);
        $this->assertContains("\"notifier\":\"{NOTIFIER}\"", $encoded);
    }

    private function mockSerialize($mock, $returnVal)
    {
        if (is_string($mock)) {
            $mock = m::mock("$mock, \JsonSerializable");
        }
        return $mock->shouldReceive("jsonSerialize")
            ->andReturn($returnVal)
            ->mock();
    }
}
