<?php

namespace Rollbar\Truncation;

use Rollbar\DataBuilder;
use Rollbar\LevelFactory;
use Rollbar\Utilities;

class RawStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $payload = array('test' => 'test data');
        
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));

        $strategy = new RawStrategy($dataBuilder);
        $result = $strategy->execute($payload);
        
        $this->assertEquals(
            strlen(json_encode($payload)),
            strlen(json_encode($result))
        );
    }
}
