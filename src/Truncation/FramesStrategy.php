<?php namespace Rollbar\Truncation;

class FramesStrategy extends AbstractStrategy
{
    
    const FRAMES_OPTIMIZATION_RANGE = 75;
    
    public function execute(array $payload)
    {
		$trace_or_chain = false;

        if (isset($payload['data']['body']['trace_chain']['frames'])) {
			$trace_or_chain = 'trace_chain';
        } elseif (isset($payload['data']['body']['trace']['frames'])) {
			$trace_or_chain = 'trace';
        }

		if ($trace_or_chain) {
			$payload['data']['body'][$trace_or_chain]['frames'] = $this->selectFrames($payload['data']['body'][$trace_or_chain]['frames']);
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
}
