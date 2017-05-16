<?php

namespace Rollbar\Truncation;

use Rollbar\DataBuilder;

class RawStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $payload = array('test' => 'test data');
        
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests'
        ));

        $strategy = new RawStrategy($dataBuilder);
        $result = $strategy->execute($payload);
        
        $this->assertEquals(
            strlen(json_encode($payload)),
            strlen(json_encode($result))
        );
    }
}
