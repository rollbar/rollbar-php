<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Payload;
use Rollbar\Payload\Body;

class PayloadTest extends \PHPUnit_Framework_TestCase
{
    public function testPayloadConstructorRequiresBody()
    {
        // PHPUnit converts errors to exceptions
        $this->setExpectedException("\PHPUnit_Framework_Error");
        $payload = new Payload();
    }

    public function testPayloadBody()
    {
        $bodyContent = m::mock("Rollbar\Payload\ContentInterface");
        $body = new Body($bodyContent);
        $payload = new Payload($body);

        $this->assertEquals($body, $payload->getBody());
    }

    public function testPayloadAccessToken()
    {
        $bodyContent = m::mock("Rollbar\Payload\ContentInterface");
        $body = new Body($bodyContent);

        $accessToken = null;
        $payload = new Payload($body, $accessToken);
        $this->assertNull($payload->getAccessToken());

        $accessToken = "too_short";
        try {
            new Payload($body, $accessToken);
        } catch (\InvalidArgumentException $e) {
            $this->assertContains("32", $e->getMessage());
        }

        $accessToken = "too_longtoo_longtoo_longtoo_longtoo_longtoo_long";
        try {
            new Payload($body, $accessToken);
        } catch (\InvalidArgumentException $e) {
            $this->assertContains("32", $e->getMessage());
        }

        $accessToken = "012345678901234567890123456789ab";
        $payload = new Payload($body, $accessToken);
        $this->assertEquals($accessToken, $payload->getAccessToken());
    }
}
