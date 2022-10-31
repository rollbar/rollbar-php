<?php namespace Rollbar;

use Mockery as m;
use Rollbar\Payload\Data;
use Rollbar\Payload\Level;
use Rollbar\Payload\Notifier;
use Rollbar\Payload\Server;
use Rollbar\Payload\Person;
use Rollbar\Payload\Request;
use Rollbar\Payload\Body;

class DataTest extends BaseRollbarTest
{
    private m\LegacyMockInterface|Body|m\MockInterface $body;
    private Data $data;

    public function setUp(): void
    {
        $this->body = m::mock(Body::class);
        $this->data = new Data("test", $this->body);
    }

    public function testEnvironmentMustBeString(): void
    {
        $data = new Data("env", $this->body);
        $this->assertEquals("env", $data->getEnvironment());

        $this->assertEquals("test", $data->setEnvironment("test")->getEnvironment());
    }

    public function testBody(): void
    {
        $data = new Data("env", $this->body);
        $this->assertEquals($this->body, $data->getBody());

        $body2 = m::mock(Body::class);
        $this->assertEquals($body2, $data->setBody($body2)->getBody());
    }

    public function testLevel(): void
    {
        $level = Level::ERROR;
        
        $this->assertEquals(
            LevelFactory::fromName($level),
            $this->data->setLevel($level)->getLevel()
        );
    }

    public function testTimestamp(): void
    {
        $timestamp = time();
        $this->assertEquals($timestamp, $this->data->setTimestamp($timestamp)->getTimestamp());
    }

    public function testCodeVersion(): void
    {
        $codeVersion = "v0.18.1";
        $this->assertEquals($codeVersion, $this->data->setCodeVersion($codeVersion)->getCodeVersion());
    }

    public function testPlatform(): void
    {
        $platform = "Linux";
        $this->assertEquals($platform, $this->data->setPlatform($platform)->getPlatform());
    }

    public function testLanguage(): void
    {
        $language = "PHP";
        $this->assertEquals($language, $this->data->setLanguage($language)->getLanguage());
    }

    public function testFramework(): void
    {
        $framework = "Laravel";
        $this->assertEquals($framework, $this->data->setFramework($framework)->getFramework());
    }

    public function testContext(): void
    {
        $context = "SuperController->getResource()";
        $this->assertEquals($context, $this->data->setContext($context)->getContext());
    }

    public function testRequest(): void
    {
        $request = m::mock(Request::class);
        $this->assertEquals($request, $this->data->setRequest($request)->getRequest());
    }

    public function testPerson(): void
    {
        $person = m::mock(Person::class);
        $this->assertEquals($person, $this->data->setPerson($person)->getPerson());
    }

    public function testServer(): void
    {
        $server = m::mock(Server::class);
        $this->assertEquals($server, $this->data->setServer($server)->getServer());
    }

    public function testCustom(): void
    {
        $custom = array(
            "x" => 1,
            "y" => 2,
            "z" => 3,
        );
        $this->assertEquals($custom, $this->data->setCustom($custom)->getCustom());
    }

    public function testFingerprint(): void
    {
        $fingerprint = "bad-error-with-database";
        $this->assertEquals($fingerprint, $this->data->setFingerprint($fingerprint)->getFingerprint());
    }

    public function testTitle(): void
    {
        $title = "End of the World as we know it";
        $this->assertEquals($title, $this->data->setTitle($title)->getTitle());
    }

    public function testUuid(): void
    {
        $uuid = "21EC2020-3AEA-4069-A2DD-08002B30309D";
        $this->assertEquals($uuid, $this->data->setUuid($uuid)->getUuid());
    }

    public function testNotifier(): void
    {
        $notifier = m::mock(Notifier::class);
        $this->assertEquals($notifier, $this->data->setNotifier($notifier)->getNotifier());
    }

    public function testEncode(): void
    {
        $time = time();
        $level = $this->mockSerialize(Level::class, "{LEVEL}");
        $body = $this->mockSerialize($this->body, "{BODY}");
        $request = $this->mockSerialize(Request::class, "{REQUEST}");
        $person = $this->mockSerialize(Person::class, "{PERSON}");
        $server = $this->mockSerialize(Server::class, "{SERVER}");
        $notifier = $this->mockSerialize(Notifier::class, "{NOTIFIER}");

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
            ->setCustom(array("x" => "hello", "extra" => array('key'=>'val')))
            ->setFingerprint("big-fingerprint")
            ->setTitle("The Title")
            ->setUuid("123e4567-e89b-12d3-a456-426655440000")
            ->setNotifier($notifier);

        $encoded = json_encode($data->serialize());

        $this->assertStringContainsString("\"environment\":\"testing\"", $encoded);
        $this->assertStringContainsString("\"body\":\"{BODY}\"", $encoded);
        $this->assertStringContainsString("\"level\":\"{LEVEL}\"", $encoded);
        $this->assertStringContainsString("\"timestamp\":$time", $encoded);
        $this->assertStringContainsString("\"code_version\":\"v0.17.3\"", $encoded);
        $this->assertStringContainsString("\"platform\":\"LAMP\"", $encoded);
        $this->assertStringContainsString("\"language\":\"PHP 5.4\"", $encoded);
        $this->assertStringContainsString("\"framework\":\"CakePHP\"", $encoded);
        $this->assertStringContainsString("\"context\":\"AppController->updatePerson\"", $encoded);
        $this->assertStringContainsString("\"request\":\"{REQUEST}\"", $encoded);
        $this->assertStringContainsString("\"person\":\"{PERSON}\"", $encoded);
        $this->assertStringContainsString("\"server\":\"{SERVER}\"", $encoded);
        $this->assertStringContainsString("\"custom\":{\"x\":\"hello\",\"extra\":{\"key\":\"val\"}}", $encoded);
        $this->assertStringContainsString("\"fingerprint\":\"big-fingerprint\"", $encoded);
        $this->assertStringContainsString("\"title\":\"The Title\"", $encoded);
        $this->assertStringContainsString("\"uuid\":\"123e4567-e89b-12d3-a456-426655440000\"", $encoded);
        $this->assertStringContainsString("\"notifier\":\"{NOTIFIER}\"", $encoded);
    }

    private function mockSerialize($mock, $returnVal)
    {
        if (is_string($mock)) {
            $mock = m::mock("$mock, Rollbar\SerializerInterface");
        }
        return $mock->shouldReceive("serialize")
            ->andReturn($returnVal)
            ->mock();
    }
}
