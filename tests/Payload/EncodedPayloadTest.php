<?php namespace Rollbar\Payload;

class EncodedPayloadTest extends \Rollbar\BaseRollbarTest
{
    public function testEncode()
    {
        $expected = '{"foo":"bar"}';
        
        $data = array(
            "foo" => "bar"
        );
        
        $encoded = new EncodedPayload($data);
        $encoded->encode();
        
        $this->assertEquals($expected, $encoded);
        
        $expected = '{"new":"bar"}';
        $encoded->encode(array("new" => "bar"));
        
        $this->assertEquals($expected, $encoded);
    }
}
