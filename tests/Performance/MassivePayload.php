<?php

namespace Rollbar\Performance;

use \Rollbar\Truncation\StringsStrategyTest;
use \Rollbar\Truncation\FramesStrategyTest;

use \Rollbar\Truncation\StringsStrategy;

class MassivePayload
{

    public function executeProvider()
    {
        $framesTest = new FramesStrategyTest();
        
        $stringData = $this->generateStringData();
        
        $data = $framesTest->executeProvider();
        $data = $data['truncate middle using trace key'][0];
        foreach ($data['data']['body']['trace']['frames'] as $i => $frame) {
            $data['data']['body']['trace']['frames'][$i] = $stringData;
        }
        
        return $data;
    }
    
    public function generateStringData()
    {
        $stringsTest = new StringsStrategyTest();
        
        $data = $stringsTest->executeProvider();
        
        $thresholds = StringsStrategy::getThresholds();
        $biggestThreshold = $thresholds[0];
        
        $data = $data['truncate strings to ' . $biggestThreshold][0];
        
        return $data['data']['body']['message']['body']['value'];
    }
}
