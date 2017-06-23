<?php

namespace Rollbar\Truncation;

class TruncationTest extends \PHPUnit_Framework_TestCase
{
    private $truncate;

    public function setUp()
    {
        $this->truncate = new Truncation();
    }

    /**
     * @dataProvider truncateProvider
     */
    public function testTruncate($data)
    {
        $result = $this->truncate->truncate($data);
        
        $size = strlen(json_encode($result));
        
        $this->assertTrue(
            $size <= Truncation::MAX_PAYLOAD_SIZE,
            "Truncation failed. Payload size exceeds MAX_PAYLOAD_SIZE."
        );
    }
    
    public function truncateProvider()
    {
        
        $stringsTest = new StringsStrategyTest();
        $framesTest = new FramesStrategyTest();
        $minBodyTest = new MinBodyStrategyTest();
        
        $data = array_merge(
            $stringsTest->executeProvider(),
            $framesTest->executeProvider(),
            $minBodyTest->executeProvider()
        );
        
        return $data;
    }
}
