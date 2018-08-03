<?php

namespace Rollbar\Truncation;

use Rollbar\Payload\EncodedPayload;
use \Rollbar\Config;
use \Rollbar\BaseRollbarTest;

class RawStrategyTest extends BaseRollbarTest
{
    public function testExecute()
    {
        $payload = array('test' => 'test data');

        $config = new Config(array('access_token' => $this->getTestAccessToken()));
        $truncation = new Truncation($config);
                    
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
