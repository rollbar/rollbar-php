<?php

namespace Rollbar\Truncation;

use \Rollbar\Config;
use \Rollbar\BaseRollbarTest;
use \Rollbar\Payload\EncodedPayload;

class TruncationTest extends BaseRollbarTest
{
    
    public function setUp()
    {
        $config = new Config(array('access_token' => $this->getTestAccessToken()));
        $this->truncate = new \Rollbar\Truncation\Truncation($config);
    }
    
    public function testCustomTruncation()
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'custom_truncation' => 'Rollbar\TestHelpers\CustomTruncation'
        ));
        $this->truncate = new \Rollbar\Truncation\Truncation($config);
        
        $data = new EncodedPayload(array(
            "data" => array(
                "body" => array(
                    "message" => array(
                        "body" => array(
                            "value" => str_repeat('A', 1000 * 1000)
                        )
                    )
                )
            )
        ));
        $data->encode();
        
        $result = $this->truncate->truncate($data);
        
        $this->assertFalse(strpos($data, 'Custom truncation test string') === false);
    }

    /**
     * @dataProvider truncateProvider
     */
    public function testTruncateNoPerformance($data)
    {
        
        $data = new \Rollbar\Payload\EncodedPayload($data);
        $data->encode();
        
        $result = $this->truncate->truncate($data);
        
        $size = strlen(json_encode($result));
        
        $this->assertTrue(
            $size <= \Rollbar\Truncation\Truncation::MAX_PAYLOAD_SIZE,
            "Truncation failed. Payload size exceeds MAX_PAYLOAD_SIZE."
        );
    }
    
    public function truncateProvider()
    {
        
        $stringsTest = new StringsStrategyTest();
        $framesTest = new FramesStrategyTest();

        $framesTestData = $framesTest->executeProvider();
        
        // Fill up frames with data to go over the allowed payload size limit
        $frames = &$framesTestData['truncate middle using trace key'][0]['data']['body']['trace']['frames'];
        $stringValue = str_repeat('A', 1024 * 10);
        foreach ($frames as $key => $data) {
            $frames[$key] = $stringValue;
        }
        
        $frames = &$framesTestData['truncate middle using trace_chain key'][0]['data']['body']['trace_chain']['frames'];
        foreach ($frames as $key => $data) {
            $frames[$key] = $stringValue;
        }
        
        $data = array_merge(
            $stringsTest->executeTruncateNothingProvider(),
            $stringsTest->executearrayProvider(),
            $framesTestData
        );
        
        return $data;
    }
}
