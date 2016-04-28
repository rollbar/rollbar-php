<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Payload;
use Rollbar\Payload\Body;

class PayloadTest extends \PHPUnit_Framework_TestCase {
    public function testPayloadConstructorRequiresBody() {
        // PHPUnit converts errors to exceptions
        $this->setExpectedException("\PHPUnit_Framework_Error");
        $payload = new Payload();
    }

    public function testPayloadConstructorAcceptsBody() {
        $bodyContent = m::mock("Rollbar\Payload\ContentInterface");
        $payload = new Payload(new Body($bodyContent));
    }

    public function testPayloadConstructorAcceptsAccessToken() {
        $bodyContent = m::mock("Rollbar\Payload\ContentInterface");
        $accessToken = "abcdef0123456789abcdef0123456789";
        $payload = new Payload(new Body($bodyContent), $accessToken);

        $this->assertEquals($accessToken, $payload->getAccessToken());
    }
}
