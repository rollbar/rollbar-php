<?php namespace Rollbar\Truncation;

use \Rollbar\Payload\EncodedPayload;

class MinBodyStrategy extends AbstractStrategy
{
    
    const EXCEPTION_MESSAGE_LIMIT = 256;
    const EXCEPTION_FRAMES_RANGE = 1;
    
    public function execute(EncodedPayload $payload)
    {
        $data = $payload->data();
        
        $modified = false;
        
        $traceData = array();
        
        if (isset($data['data']['body']['trace'])) {
            $traceData = &$data['data']['body']['trace'];
        } elseif (isset($data['data']['body']['trace_chain'])) {
            $traceData = &$data['data']['body']['trace_chain'];
        }
        
        if (isset($traceData['exception'])) {
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
            
            $modified = true;
        }
        
        /**
         * Limit trace frames
         */
        if (!empty($traceData['frames'])) {
            $framesStrategy = new FramesStrategy($this->truncation);
            $traceData['frames'] = $framesStrategy->selectFrames(
                $traceData['frames'],
                static::EXCEPTION_FRAMES_RANGE
            );
            
            $modified = true;
        }
        
        if ($modified) {
            $payloadClass = get_class($payload);
            $payload = new $payloadClass($data);
            $payload->encode();
        }
        
        return $payload;
    }
}
