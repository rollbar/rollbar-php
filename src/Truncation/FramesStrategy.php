<?php namespace Rollbar\Truncation;

class FramesStrategy implements IStrategy
{
    
    const FRAMES_OPTIMIZATION_RANGE = 150;
    
    public function execute(array $payload)
    {
        $frames = array();
        
        if (isset($payload['data']['body']['trace_chain']['frames'])) {
            
            $frames = $payload['data']['body']['trace_chain']['frames'];
            
        } elseif (isset($payload['data']['body']['trace']['frames'])) {
            
            $frames = $payload['data']['body']['trace']['frames'];
            
        }
        
        return $this->selectFrames($frames);
    }
    
    protected function selectFrames($frames, $range = self::FRAMES_OPTIMIZATION_RANGE)
    {
        if (count($frames) <= $range * 2) {
            return $frames;
        }
        
        return array_merge(
            array_splice($frames, 0, $range),
            array_splice($frames, -$range, $range)
        );
    }
}