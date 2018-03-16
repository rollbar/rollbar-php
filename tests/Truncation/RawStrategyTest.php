<?php

namespace Rollbar\Truncation;

use Rollbar\Payload\EncodedPayload;

class RawStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $payload = array('test' => 'test data');

        $truncation = new Truncation();
                    
        $strategy = new RawStrategy($truncation);
        
        $data = new EncodedPayload($payload);
        $data->encode();
        
        $result = $strategy->execute($data);
        
        $this->assertEquals(
            strlen(json_encode($payload)),
            $result->size()
        );
    }
}
