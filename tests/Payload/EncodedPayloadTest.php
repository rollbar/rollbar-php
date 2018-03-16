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
    
    public function testEncodeMalformedData()
    {
        $encoded = new EncodedPayload(array(
            'data' => array(
                'body' => array(
                    'exception' => array(
                        'trace' => array(
                            'frames' => fopen('/dev/null', 'r')
                        ),
                    ),
                    'ecodable1' => true
                ),
                'ecodable2' => true
            )
        ));
        $encoded->encode();
        
        $result = json_decode($encoded->encoded(), true);
        
        $this->assertNull($result['data']['body']['exception']['trace']['frames']);
    }
}
