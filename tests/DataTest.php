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
        } catch (\InvalidArgumentException $e) {
            $this->assertContains("must be a string", $e->getMessage());
        }

        try {
            $data = new Data(null, $this->body);
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
}
