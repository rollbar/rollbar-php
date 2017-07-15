<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Body;

class BodyTest extends BaseRollbarTest
{
    public function testBodyValue()
    {
        $value = m::mock("Rollbar\Payload\ContentInterface");
        $body = new Body($value);
        $this->assertEquals($value, $body->getValue());

        $mock2 = m::mock("Rollbar\Payload\ContentInterface");
        $this->assertEquals($mock2, $body->setValue($mock2)->getValue());
    }

    public function testEncode()
    {
        $value = m::mock("Rollbar\Payload\ContentInterface")
            ->shouldReceive("jsonSerialize")
            ->andReturn("{CONTENT}")
            ->shouldReceive("getKey")
            ->andReturn("content_interface")
            ->mock();
        $body = new Body($value);
        $encoded = json_encode($body->jsonSerialize());
        $this->assertEquals("{\"content_interface\":\"{CONTENT}\"}", $encoded);
    }
}
