<?php

namespace Rollbar\Truncation;

class RawStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $payload = array('test' => 'test data');

        $strategy = new RawStrategy();
        $result = $strategy->execute($payload);
        
        $this->assertEquals(
            strlen(json_encode($payload)),
            strlen(json_encode($result))
        );
    }
}
