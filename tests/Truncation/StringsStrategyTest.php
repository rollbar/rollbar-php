<?php

namespace Rollbar\Truncation;

use Rollbar\Payload\EncodedPayload;
use Rollbar\Config;
use Rollbar\BaseRollbarTest;

class StringsStrategyTest extends BaseRollbarTest
{
    
    protected function execute($data): array
    {
        $config = new Config(array('access_token' => $this->getTestAccessToken()));
        $truncation = new Truncation($config);

        $strategy = new StringsStrategy($truncation);
        
        $data = new EncodedPayload($data);
        $data->encode();
        
        return $strategy->execute($data)->data();
    }
    
    /**
     * @dataProvider executeTruncateNothingProvider
     */
    public function testExecuteTruncateNothing($data, $expected): void
    {
        $result = $this->execute($data);
        $this->assertEquals($expected, $result);
    }

    /**
     * Also used by {@see TruncationTest::truncateProvider()}.
     *
     * @return array
     */
    public function executeTruncateNothingProvider(): array
    {
        $data = array();
        
        $data["truncate nothing"] = array(
            $this->payloadStructureProvider(str_repeat("A", 10)),
            $this->payloadStructureProvider(str_repeat("A", 10))
        );
        
        return $data;
    }
    
    /**
     * @dataProvider executeArrayProvider
     */
    public function testExecuteArray($data, $expectedThreshold): void
    {
        $result = $this->execute($data);
        
        foreach ($result['data']['body']['message']['body']['value'] as $key => $toTrim) {
            $this->assertTrue(
                strlen($toTrim) <= $expectedThreshold,
                "The string '$toTrim' was expected to be trimmed to " . $expectedThreshold . " characters. " .
                "Actual length: " . strlen($toTrim)
            );
        }
    }

    /**
     * Also used by {@see TruncationTest::truncateProvider()}.
     *
     * @return array
     */
    public function executeArrayProvider(): array
    {
        $data = array();
        
        $thresholds = StringsStrategy::getThresholds();
        foreach ($thresholds as $threshold) {
            $data['truncate strings to ' . $threshold] = $this->thresholdTestProvider($threshold);
        }
        
        return $data;
    }
    
    public function thresholdTestProvider($threshold): array
    {
        $stringLengthToTrim = $threshold*2;
        
        $payload = $this->payloadStructureProvider(array());
        $payload['data']['body']['message']['body']['value2'] = array();
        
        while (strlen(json_encode($payload)) <= Truncation::MAX_PAYLOAD_SIZE) {
            $payload['data']['body']['message']['body']['value'] []=
                str_repeat('A', $stringLengthToTrim);
            $payload['data']['body']['message']['body']['value2'] []=
                str_repeat('A', $stringLengthToTrim);
        }
        
        return array($payload, $threshold);
    }

    public function executeProvider(): array
    {
        $data = array();

        $data["truncate nothing"] = array(
            $this->payloadStructureProvider(str_repeat("A", 10)),
            $this->payloadStructureProvider(str_repeat("A", 10))
        );

        $thresholds = StringsStrategy::getThresholds();
        foreach ($thresholds as $threshold) {
            $data['truncate strings to ' . $threshold] = $this->thresholdTestProvider($threshold);
        }

        return $data;
    }
    
    public function payloadStructureProvider($message): array
    {
        return array(
            "data" => array(
                "body" => array(
                    "message" => array(
                        "body" => array(
                            "value" => $message
                        )
                    )
                )
            )
        );
    }
}
