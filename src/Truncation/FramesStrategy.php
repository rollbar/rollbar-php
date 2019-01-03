<?php namespace Rollbar\Truncation;

use \Rollbar\Payload\EncodedPayload;

class FramesStrategy extends AbstractStrategy
{

    const FRAMES_OPTIMIZATION_RANGE = 75;

    public function execute(EncodedPayload $payload)
    {
        $data = $payload->data();

        if (isset($data['data']['body']['trace_chain'])) {
            foreach ($data['data']['body']['trace_chain'] as $offset => $value) {
                $data['data']['body']['trace_chain'][$offset]['frames'] = $this->selectFrames($value['frames']);
            }
            
            $payload->encode($data);
        } elseif (isset($data['data']['body']['trace']['frames'])) {
            $data['data']['body']['trace']['frames'] = $this->selectFrames($data['data']['body']['trace']['frames']);
            $payload->encode($data);
        }

        return $payload;
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
    
    public function applies(EncodedPayload $payload)
    {
        $payload = $payload->data();
        
        if (isset($payload['data']['body']['trace_chain']) ||
            isset($payload['data']['body']['trace']['frames'])) {
            return true;
        }
        
        return false;
    }
}
