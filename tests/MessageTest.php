<?php namespace Rollbar\Payload;

use Rollbar;

class MessageTest extends Rollbar\BaseRollbarTest
{
    public function testBacktrace()
    {
        $expected = array('trace 1' => 'value 1');
        $msg = new Message("Test", array(), $expected);
        $this->assertEquals($expected, $msg->getBacktrace());
    }
    
    public function testBody()
    {
        $msg = new Message("Test");
        $this->assertEquals("Test", $msg->getBody());

        $this->assertEquals("Test2", $msg->setBody("Test2")->getBody());
    }

    public function testExtra()
    {
        $msg = new Message("M", array(
            "hello" => "world"
        ));
        $this->assertEquals("world", $msg->hello);
        $msg->hello = "Świat"; // Polish for "World"
        $this->assertEquals("Świat", $msg->hello);
        // Unicode Ś == u015a
        $this->assertEquals('{"body":"M","hello":"\u015awiat"}', json_encode($msg->jsonSerialize()));
    }

    public function testMessageCustom()
    {
        $msg = new Message("Test");
        $msg->CustomData = "custom data";
        $msg->whatever = 15;

        $this->assertEquals("custom data", $msg->CustomData);
        $this->assertEquals(15, $msg->whatever);

        $expected = '{"body":"Test","CustomData":"custom data","whatever":15}';
        $this->assertEquals($expected, json_encode($msg->jsonSerialize()));
    }

    public function testMessageKey()
    {
        $msg = new Message("Test");
        $this->assertEquals("message", $msg->getKey());
    }
}
