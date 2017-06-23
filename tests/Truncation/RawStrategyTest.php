<?php

namespace Rollbar\Truncation;

class RawStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $payload = array('test' => 'test data');

        $truncation = new Truncation();
                    
        $strategy = new RawStrategy($truncation);
        $result = $strategy->execute($payload);
        
        $this->assertEquals(
            strlen(json_encode($payload)),
            strlen(json_encode($result))
        );
    }
}
