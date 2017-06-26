<?php namespace Rollbar\Truncation;

class MinBodyStrategy extends AbstractStrategy
{
    
    const EXCEPTION_MESSAGE_LIMIT = 256;
    const EXCEPTION_FRAMES_RANGE = 1;
    
    public function execute(array $payload)
    {
        
        $traceData = null;
        
        if (isset($payload['data']['body']['trace'])) {
            $traceData = &$payload['data']['body']['trace'];
        } elseif (isset($payload['data']['body']['trace_chain'])) {
            $traceData = &$payload['data']['body']['trace_chain'];
        }
        
        /**
         * Delete exception description
         */
        unset($traceData['exception']['description']);
        
        /**
         * Truncate exception message
         */
        $traceData['exception']['message'] = substr(
            $traceData['exception']['message'],
            0,
            static::EXCEPTION_MESSAGE_LIMIT
        );
        
        /**
         * Limit trace frames
         */
        $framesStrategy = new FramesStrategy($this->truncation);
        $traceData['frames'] = $framesStrategy->selectFrames(
            $traceData['frames'],
            static::EXCEPTION_FRAMES_RANGE
        );
        
        return $payload;
    }
}
