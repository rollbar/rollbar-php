<?php

namespace Rollbar\Truncation;

use Rollbar\TestHelpers\TruncationPerformance;
use Rollbar\Payload\EncodedPayload;

class TruncationTest extends \PHPUnit_Framework_TestCase
{
    
    public function setUp()
    {
        $this->truncate = new Truncation();
    }

    /**
     * @dataProvider truncateProvider
     */
    public function testTruncateNoPerformance($data)
    {
        
        $data = new EncodedPayload($data);
        $data->encode();
        
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

        $framesTestData = $framesTest->executeProvider();
        
        // Fill up frames with data to go over the allowed payload size limit
        $stringValue = str_repeat('A', 1024 * 10);  
        foreach ($framesTestData['truncate middle using trace key'][0]['data']['body']['trace']['frames'] as $key => $data) {
            $framesTestData['truncate middle using trace key'][0]['data']['body']['trace']['frames'][$key] = $stringValue;
        }
        
        foreach ($framesTestData['truncate middle using trace_chain key'][0]['data']['body']['trace_chain']['frames'] as $key => $data) {
            $framesTestData['truncate middle using trace_chain key'][0]['data']['body']['trace_chain']['frames'][$key] = $stringValue;
        }
        
        $data = array_merge(
            $stringsTest->executeProvider(),
            $framesTestData
        );
        
        return $data;
    }
    
    /**
     * 
     * = Optimization notes =
     * 
     * == testTruncatePerformance for StringsStrategyTest - truncate strings to 1024 ==
     * 
     * === Before any optimizations ===
     * Payload size: 524330 bytes = 0.5 MB 
     * Strategies used: Rollbar\Truncation\RawStrategy, Rollbar\Truncation\FramesStrategy, Rollbar\Truncation\StringsStrategy
     * Encoding triggered: 6
     * Memory usage: 0 bytes = 0 MB
     * Execution time: 9.833740234375 ms
     * 
     * === After removing RawStrategy and MinBodyStrategy ===
     * Payload size: 524330 bytes = 0.5 MB 
     * Strategies used: Rollbar\Truncation\FramesStrategy, Rollbar\Truncation\StringsStrategy
     * Encoding triggered: 4
     * Memory usage: 0 bytes = 0 MB
     * Execution time: 6.991943359375 ms
     * 
     * === After adding applies() in strategies ===
     * Payload size: 524330 bytes = 0.5 MB 
     * Strategies used: Rollbar\Truncation\StringsStrategy
     * Encoding triggered: 3
     * Memory usage: 0 bytes = 0 MB
     * Execution time: 6.03076171875 ms
     * 
     * 
     * 
     * 
     * 
     * 
     * 
     * 
     * == testTruncatePerformance for FramesStrategyTest - nothing to truncate using trace key ==
     * 
     * === Before any optimizations ===
     * Payload size: 52 bytes = 0 MB 
     * Strategies used: 
     * Encoding triggered: 1
     * Memory usage: 0 bytes = 0 MB
     * Execution time: 0.002685546875 ms
     * 
     * === After removing RawStrategy and MinBodyStrategy ===
     * Payload size: 52 bytes = 0 MB 
     * Strategies used: 
     * Encoding triggered: 1
     * Memory usage: 0 bytes = 0 MB
     * Execution time: 0.0029296875 ms
     * 
     * === After adding applies() in strategies ===
     * Payload size: 52 bytes = 0 MB 
     * Strategies used: 
     * Encoding triggered: 1
     * Memory usage: 0 bytes = 0 MB
     * Execution time: 0.010986328125 ms
     * 
     * 
     * 
     * 
     * 
     * 
     * == testTruncatePerformance for MassivePayloadTest ==
     * 
     * === Before any optimizations ===
     * Payload size: 79166622 bytes = 75.5 MB 
     * Strategies used: Rollbar\Truncation\RawStrategy, Rollbar\Truncation\FramesStrategy, Rollbar\Truncation\StringsStrategy, Rollbar\Truncation\MinBodyStrategy
     * Encoding triggered: 7
     * Memory usage: 174063616 bytes = 166 MB
     * Execution time: 2382.2009277344 ms
     * 
     * === After removing RawStrategy and MinBodyStrategy ===
     * Payload size: 79166622 bytes = 75.5 MB 
     * Strategies used: Rollbar\Truncation\FramesStrategy, Rollbar\Truncation\StringsStrategy
     * Encoding triggered: 5
     * Memory usage: 174063616 bytes = 166 MB
     * Execution time: 2074.6579589844 ms
     * 
     * === After adding applies() in strategies ===
     * Payload size: 79166622 bytes = 75.5 MB 
     * Strategies used: Rollbar\Truncation\FramesStrategy, Rollbar\Truncation\StringsStrategy
     * Encoding triggered: 5
     * Memory usage: 174063616 bytes = 166 MB
     * Execution time: 1998.2878417969 ms
     * 
     * 
     * 
     */
    
    /**
     * @dataProvider truncatePerformanceProvider
     */
    public function testTruncatePerformance($dataName, $data)
    {
        echo "\n== testTruncatePerformance for $dataName ==\n";
        
        $truncation = new TruncationPerformance();
        
        $data = new EncodedPayload($data);
        $data->encode();
        
        $result = $truncation->truncate($data);
        
        echo $truncation->getLastRun();
    }
    
    public function truncatePerformanceProvider()
    {
        $stringsTest = new StringsStrategyTest();
        $framesTest = new FramesStrategyTest();
        $minBodyTest = new MinBodyStrategyTest();
        $massivePayloadTest = new MassivePayload();
        
        $stringsTestData = $stringsTest->executeProvider();
        $stringsTestData = $stringsTestData['truncate strings to 1024'][0];
        
        $framesTestData = $framesTest->executeProvider();
        $framesTestData = $framesTestData['nothing to truncate using trace key'][0];
        
        $data = array(
            array(
                "StringsStrategyTest - truncate strings to 1024",
                $stringsTestData
            ),
            array(
                "FramesStrategyTest - nothing to truncate using trace key",
                $framesTestData
            ),
            array(
                "MassivePayloadTest",
                $massivePayloadTest->executeProvider()
            )
        );
        
        return $data;
    }
}
