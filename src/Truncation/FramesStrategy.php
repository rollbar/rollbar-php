<?php namespace Rollbar\Truncation;

class FramesStrategy extends AbstractStrategy
{
    
    const FRAMES_OPTIMIZATION_RANGE = 75;
    
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
    
    public function selectFrames($frames, $range = self::FRAMES_OPTIMIZATION_RANGE)
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
