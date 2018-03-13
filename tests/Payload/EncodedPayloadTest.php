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
        $this->assertEquals(1, EncodedPayload::GetEncodingCount());
    }
}
