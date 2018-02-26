<?php namespace Rollbar\Truncation;

class FramesStrategy extends AbstractStrategy
{

    const FRAMES_OPTIMIZATION_RANGE = 75;

    public function execute(array $payload)
    {
        $key = false;

        if (isset($payload['data']['body']['trace_chain']['frames'])) {
            $key = 'trace_chain';
        } elseif (isset($payload['data']['body']['trace']['frames'])) {
            $key = 'trace';
        }

        if ($key) {
            $payload['data']['body'][$key]['frames'] = $this->selectFrames($payload['data']['body'][$key]['frames']);
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
