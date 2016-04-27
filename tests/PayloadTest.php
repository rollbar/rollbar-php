<?php namespace Rollbar;

use Rollbar\Payload\Payload;
use Rollbar\Payload\Body;

class PayloadTest extends \PHPUnit_Framework_TestCase {
    public function testPayloadConstructorRequiresBody() {
        // PHPUnit converts errors to exceptions
        $this->setExpectedException("\PHPUnit_Framework_Error");
        $payload = new Payload();
    }
}
