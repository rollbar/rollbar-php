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
        $thresholds = StringsStrategy::getThresholds();
        
        // $data["truncate nothing"] = array(
        //     $this->payloadStructureProvider(str_repeat("A", 10)),
        //     $this->payloadStructureProvider(str_repeat("A", 10))
        // );
        
        // $threshold = $tresholds[0];
        // $data['truncate strings to ' . $threshold] = array(
        //     $this->payloadStructureProvider(str_repeat('A', DataBuilder::MAX_PAYLOAD_SIZE+1)),
        //     $this->payloadStructureProvider(str_repeat('A', $threshold))
        // );
        
        $threshold = $thresholds[1];
        $stringLengthToTrim = $threshold+1;
        
        $payloadStrings = $expectedStrings = array();
        
        $numStrings = floor(DataBuilder::MAX_PAYLOAD_SIZE / $stringLengthToTrim);
        
        $staticNoise = strlen(json_encode($this->payloadStructureProvider(array(""))));
        $dynamicNoise = strlen(json_encode($this->payloadStructureProvider(array("","")))) - $staticNoise;
        
        for ($i = 0; $i < $numStrings; $i++) {
            $payloadStrings []= str_repeat('A', $stringLengthToTrim);
            $expectedStrings []= str_repeat('A', $threshold);
        }
        
        $payload = $this->payloadStructureProvider($payloadStrings);
        $expected = $this->payloadStructureProvider($expectedStrings);
        
        $data['truncate strings to ' . $threshold] = array($payload,$expected);
        
        return $data;
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
