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

    public function testExtra()
    {
        $value = m::mock("Rollbar\Payload\ContentInterface")
            ->shouldReceive("serialize")
            ->andReturn("{CONTENT}")
            ->shouldReceive("getKey")
            ->andReturn("content_interface")
            ->mock();
        $expected = array(
            "hello" => "world"
        );
        $body = new Body($value, $expected);
        $this->assertEquals($body->getExtra(), $expected);
    }

    public function testSerialize()
    {
        $value = m::mock("Rollbar\Payload\ContentInterface")
            ->shouldReceive("serialize")
            ->andReturn("{CONTENT}")
            ->shouldReceive("getKey")
            ->andReturn("content_interface")
            ->mock();
        $body = new Body($value, array('foo' => 'bar'));
        $encoded = json_encode($body->serialize());
        $this->assertEquals(
            "{\"content_interface\":\"{CONTENT}\",\"extra\":{\"foo\":\"bar\"}}",
            $encoded
        );
    }
}
