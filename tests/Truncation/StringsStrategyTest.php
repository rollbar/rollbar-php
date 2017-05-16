<?php

namespace Rollbar\Truncation;

use Rollbar\DataBuilder;

class StringsStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider executeProvider
     */
    public function testExecute($data, $expected)
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests'
        ));
                    
        $strategy = new StringsStrategy($dataBuilder);
        $result = $strategy->execute($data);
        
        $this->assertEquals($expected, $result);
    }
    
    public function executeProvider()
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
    
    protected function thresholdTestProvider($threshold)
    {
        $stringLengthToTrim = $threshold+1;
        
        $payload = $this->payloadStructureProvider(array());
        $expected = $this->payloadStructureProvider(array());
        
        while (strlen(json_encode($payload)) < DataBuilder::MAX_PAYLOAD_SIZE) {
            $payload['data']['body']['message']['body']['value'] []=
                str_repeat('A', $stringLengthToTrim);
                
            $expected['data']['body']['message']['body']['value'] []=
                str_repeat('A', $threshold);
        }
        
        return array($payload,$expected);
    }
    
    protected function payloadStructureProvider($message)
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
