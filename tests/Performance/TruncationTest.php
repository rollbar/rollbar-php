<?php

namespace Rollbar\Performance;

use Rollbar\Performance\MassivePayload;
use Rollbar\Performance\TestHelpers\Truncation;
use Rollbar\Performance\TestHelpers\EncodedPayload;

use Rollbar\Truncation\StringsStrategyTest;
use Rollbar\Truncation\FramesStrategyTest;
use Rollbar\Truncation\MinBodyStrategyTest;

use Rollbar\Config;
use Rollbar\BaseRollbarTest;

class TruncationTest extends BaseRollbarTest
{
    
    public function setUp(): void
    {
        $config = new Config(array('access_token' => $this->getTestAccessToken()));
        $this->truncate = new Truncation($config);
    }
    
    /**
     *
     * = Optimization notes =
     *
     * == testTruncatePerformance for StringsStrategyTest - truncate strings to 1024 ==
     *
     * === Before any optimizations ===
     * Payload size: 524330 bytes = 0.5 MB
     * Strategies used:
     * Rollbar\Truncation\RawStrategy,
     * Rollbar\Truncation\FramesStrategy,
     * Rollbar\Truncation\StringsStrategy
     * Encoding triggered: 6
     * Memory usage: 0 bytes = 0 MB
     * Execution time: 9.833740234375 ms
     *
     * === After removing RawStrategy and MinBodyStrategy ===
     * Payload size: 524330 bytes = 0.5 MB
     * Strategies used:
     * Rollbar\Truncation\FramesStrategy,
     * Rollbar\Truncation\StringsStrategy
     * Encoding triggered: 4
     * Memory usage: 0 bytes = 0 MB
     * Execution time: 6.991943359375 ms
     *
     * === After adding applies() in strategies ===
     * Payload size: 524330 bytes = 0.5 MB
     * Strategies used:
     * Rollbar\Truncation\StringsStrategy
     * Encoding triggered: 3
     * Memory usage: 0 bytes = 0 MB
     * Execution time: 6.03076171875 ms
     *
     * === After limiting json_encode invocations ===
     * Payload size: 524330 bytes = 0.5 MB
     * Strategies used:
     * Rollbar\Truncation\StringsStrategy
     * Encoding triggered: 1
     * Memory usage: 0 bytes = 0 MB
     * Execution time: 3.761962890625 ms
     *
     * === After replacing array_walk_recurisve with traverse ===
     * Payload size: 524330 bytes = 0.5 MB
     * Strategies used:
     * Rollbar\Truncation\StringsStrategy
     * Encoding triggered: 1
     * Memory usage: 0 bytes = 0 MB
     * Execution time: 2.003173828125 ms
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
     * === After limiting json_encode invocations ===
     * Payload size: 52 bytes = 0 MB
     * Strategies used:
     * Encoding triggered: 0
     * Memory usage: 0 bytes = 0 MB
     * Execution time: 0.01513671875 ms
     *
     * === After replacing array_walk_recurisve with traverse ===
     * Payload size: 52 bytes = 0 MB
     * Strategies used:
     * none
     * Encoding triggered: 0
     * Memory usage: 0 bytes = 0 MB
     * Execution time: 0.004150390625 ms
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
     * Strategies used:
     * Rollbar\Truncation\RawStrategy,
     * Rollbar\Truncation\FramesStrategy,
     * Rollbar\Truncation\StringsStrategy,
     * Rollbar\Truncation\MinBodyStrategy
     * Encoding triggered: 7
     * Memory usage: 174063616 bytes = 166 MB
     * Execution time: 2382.2009277344 ms
     *
     * === After removing RawStrategy and MinBodyStrategy ===
     * Payload size: 79166622 bytes = 75.5 MB
     * Strategies used:
     * Rollbar\Truncation\FramesStrategy,
     * Rollbar\Truncation\StringsStrategy
     * Encoding triggered: 5
     * Memory usage: 174063616 bytes = 166 MB
     * Execution time: 2074.6579589844 ms
     *
     * === After adding applies() in strategies ===
     * Payload size: 79166622 bytes = 75.5 MB
     * Strategies used:
     * Rollbar\Truncation\FramesStrategy,
     * Rollbar\Truncation\StringsStrategy
     * Encoding triggered: 5
     * Memory usage: 174063616 bytes = 166 MB
     * Execution time: 1998.2878417969 ms
     *
     * === After limiting json_encode invocations ===
     * Payload size: 79166622 bytes = 75.5 MB
     * Strategies used:
     * Rollbar\Truncation\FramesStrategy,
     * Rollbar\Truncation\StringsStrategy
     * Encoding triggered: 2
     * Memory usage: 181329920 bytes = 172.93 MB
     * Execution time: 1204.74609375 ms
     *
     * === After replacing array_walk_recurisve with traverse ===
     * Payload size: 79166622 bytes = 75.5 MB
     * Strategies used:
     * Rollbar\Truncation\FramesStrategy,
     * Rollbar\Truncation\StringsStrategy
     * Encoding triggered: 2
     * Memory usage: 78643200 bytes = 75 MB
     * Execution time: 838.8759765625 ms
     *
     *
     */
    
    /**
     * @dataProvider truncateProvider
     */
    public function testTruncate($dataName, $data, array $assertions = array()): void
    {
        $payload = new EncodedPayload($data);
        $payload->encode();
        
        $payload = $this->truncate->truncate($payload);
        
        $performance = $this->truncate->getLastRun();
        foreach ($assertions as $assertion) {
            $this->assertMatchesRegularExpression(
                $assertion,
                $performance,
                "Performance of $dataName did not meet expectations"
            );
        }
    }
    
    public function truncateProvider(): array
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
                $stringsTestData,
                array (
                  '/^Memory usage: (\d|[12]\d) bytes/m', // < 30 bytes
                  '/^Execution time: \d.\d+ ms/m' // < 10 ms
                )
            ),
            array(
                "FramesStrategyTest - nothing to truncate using trace key",
                $framesTestData,
                array (
                  '/^Memory usage: (\d|[12]\d) bytes/m', // < 30 bytes
                  '/^Execution time: \d.\d+ ms/m' // < 10 ms
                )
            ),
            array(
                "MassivePayloadTest",
                $massivePayloadTest->executeProvider(),
                array (
                  '/^Memory usage: (\d|[12]\d{1,7}) bytes/m', // < 20,000,000 bytes
                  '/^Execution time: (\d{1,2}.\d+) ms/m' // < 100 ms
                )
            ),
            array(
                "OneLongString",
                $stringsTest->payloadStructureProvider(
                    str_repeat("A", \Rollbar\Truncation\Truncation::MAX_PAYLOAD_SIZE+1)
                ),
                array (
                  '/^Memory usage: (\d|[12]\d) bytes/m', // < 30 bytes
                  '/^Execution time: \d.\d+ ms/m' // < 10 ms
                )
            )
        );
        
        return $data;
    }
}
