<?php

namespace Rollbar\Truncation;

class TruncationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * 
     * = Optimization notes =
     * 
     * == testTruncatePerformance for FramesStrategyTest - truncate middle using trace key ==
     * 
     * === Test data size (strlen of json_encode'd before transformations) ===
     * 536 bytes = 0 MB
     * 
     * === Before any optimizations ===
     * Truncation encoded 1 times.
     * 
     * Memory usage in truncate(): 0 MB
     * 
     * Execution time in truncate(): 0.01513671875 ms
     * 
     * == testTruncatePerformance for MassivePayloadTest ==
     * 
     * === Test data size (strlen of json_encode'd before transformations) ===
     * 79166604 bytes = 75.50 MB
     * 
     * === Before any optimizations ===
     * Truncation encoded 7 times.
     * 
     * Memory usage in truncate(): 174063616 bytes
     * 
     * Execution time in truncate(): 2460.9509277344 ms
     * 
     */
    
    public function setUp()
    {
        $this->truncate = new Truncation();
    }

    /**
     * @dataProvider truncateProvider
     */
    public function testTruncateNoPerformance($data)
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
     * @dataProvider truncatePerformanceProvider
     */
    public function testTruncatePerformance($dataName, $data)
    {
        echo "\n== testTruncatePerformance for $dataName ==\n";
        
        echo "\n=== Test data size (strlen of json_encode'd before transformations) ===\n";
        
        $size = strlen(json_encode($data));
        
        echo "\n" . $size . " bytes = " . round($size / 1024 / 1024, 2) . " MB\n";
        
        $memUsageBefore = memory_get_usage(true);
        $timeBefore = microtime(true) * 1000;
        
        $result = $this->truncate->truncate($data);
        
        $timeAfter = microtime(true) * 1000;
        $memUsageAfter = memory_get_usage(true);
        
        $memoryUsage = $memUsageAfter - $memUsageBefore;
        $timeUsage = $timeAfter - $timeBefore;
        
        echo "\nMemory usage in truncate(): " . $memoryUsage . " MB\n";
        echo "\nExecution time in truncate(): " . $timeUsage . " ms\n";
    }
    
    public function truncatePerformanceProvider()
    {
        $stringsTest = new StringsStrategyTest();
        $framesTest = new FramesStrategyTest();
        $minBodyTest = new MinBodyStrategyTest();
        $massivePayloadTest = new MassivePayloadTest();
        
        $framesTestData = $framesTest->executeProvider();
        $framesTestData = $framesTestData['truncate middle using trace key'][0];
        
        $data = array(
            // $stringsTest->executeProvider(),
            // $framesTest->executeProvider()
            // $minBodyTest->executeProvider(),
            array(
                "FramesStrategyTest - truncate middle using trace key",
                $framesTestData
            ),
            // array(
            //     "MassivePayloadTest",
            //     $massivePayloadTest->executeProvider()
            // )
        );
        
        return $data;
    }
}
