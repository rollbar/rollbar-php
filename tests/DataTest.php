<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Data;

class DataTest extends \PHPUnit_Framework_TestCase
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
    }

    public function testBody()
    {
        $data = new Data("env", $this->body);
        $this->assertEquals($this->body, $data->getBody());
    }

    public function testLevel()
    {
        $level = m::mock("Rollbar\Payload\Level");
        $this->data->setLevel($level);
        $this->assertEquals($level, $this->data->getLevel());
    }

    public function testTimestamp()
    {
        $timestamp = time();
        $this->data->setTimestamp($timestamp);
        $this->assertEquals($timestamp, $this->data->getTimestamp());
    }

    public function testCodeVersion()
    {
        $codeVersion = "v0.18.1";
        $this->data->setCodeVersion($codeVersion);
        $this->assertEquals($codeVersion, $this->data->getCodeVersion());
    }

    public function testPlatform()
    {
        $platform = "Linux";
        $this->data->setPlatform($platform);
        $this->assertEquals($platform, $this->data->getPlatform());
    }

    public function testLanguage()
    {
        $language = "PHP";
        $this->data->setLanguage($language);
        $this->assertEquals($language, $this->data->getLanguage());
    }

    public function testFramework()
    {
        $framework = "Laravel";
        $this->data->setFramework($framework);
        $this->assertEquals($framework, $this->data->getFramework());
    }

    public function testContext()
    {
        $context = "SuperController->getResource()";
        $this->data->setContext($context);
        $this->assertEquals($context, $this->data->getContext());
    }

    public function testRequest()
    {
        $request = m::mock("Rollbar\Payload\Request");
        $this->data->setRequest($request);
        $this->assertEquals($request, $this->data->getRequest());
    }

    public function testPerson()
    {
        $person = m::mock("Rollbar\Payload\Person");
        ;
        $this->data->setPerson($person);
        $this->assertEquals($person, $this->data->getPerson());
    }

    public function testServer()
    {
        $server = m::mock("Rollbar\Payload\server");
        $this->data->setServer($server);
        $this->assertEquals($server, $this->data->getServer());
    }

    public function testCustom()
    {
        $custom = array(
            "x" => 1,
            "y" => 2,
            "z" => 3,
        );
        $this->data->setCustom($custom);
        $this->assertEquals($custom, $this->data->getCustom());
    }

    public function testFingerprint()
    {
        $fingerprint = "bad-error-with-database";
        $this->data->setFingerprint($fingerprint);
        $this->assertEquals($fingerprint, $this->data->getFingerprint());
    }

    public function testTitle()
    {
        $title = "End of the World as we know it";
        $this->data->setTitle($title);
        $this->assertEquals($title, $this->data->getTitle());
    }

    public function testUuid()
    {
        $uuid = "21EC2020-3AEA-4069-A2DD-08002B30309D";
        $this->data->setUuid($uuid);
        $this->assertEquals($uuid, $this->data->getUuid());
    }

    public function testNotifier()
    {
        $notifier = m::mock("Rollbar\Payload\Notifier");
        $this->data->setNotifier($notifier);
        $this->assertEquals($notifier, $this->data->getNotifier());
    }

    public function testEncode()
    {
        $data = new Data("env", $this->mockSerialize($this->body, "{BODY}"));

        $data->setLevel($this->mockSerialize("Rollbar\Payload\Level", "{LEVEL}"));
        $time = time();
        $data->setTimestamp($time);
        $data->setCodeVersion("v0.17.3");
        $data->setPlatform("LAMP");
        $data->setLanguage("PHP 5.4");
        $data->setFramework("CakePHP");
        $data->setContext("AppController->updatePerson");
        $data->setRequest($this->mockSerialize("Rollbar\Payload\Request", "{REQUEST}"));
        $data->setPerson($this->mockSerialize("Rollbar\Payload\Person", "{PERSON}"));
        $data->setServer($this->mockSerialize("Rollbar\Payload\Server", "{SERVER}"));
        $data->setCustom(array("x" => "hello", "extra" => new \ArrayObject()));
        $data->setFingerprint("big-fingerprint");
        $data->setTitle("The Title");
        $data->setUuid("123e4567-e89b-12d3-a456-426655440000");
        $data->setNotifier($this->mockSerialize("Rollbar\Payload\Notifier", "{NOTIFIER}"));

        $encoded = json_encode($data);

        $this->assertContains("\"environment\":\"env\"", $encoded);
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
