<?php declare(strict_types=1);

namespace Rollbar\Truncation;

use Exception;
use Rollbar\Payload\EncodedPayload;

/**
 * The Frames strategy truncates long trace frame chains by keeping a specific number of frames at the beginning and
 * end. The middle frames are removed, if the list of frames is too long.
 *
 * @since 1.1.0
 */
class FramesStrategy extends AbstractStrategy
{
    /**
     * The number of frames to keep at the beginning and end of the frames array.
     */
    const FRAMES_OPTIMIZATION_RANGE = 75;

    /**
     * Truncates the data in the payload by removing excess frames from the middle of the trace chain.
     *
     * @param EncodedPayload $payload
     *
     * @return EncodedPayload
     * @throws Exception If the payload encoding fails.
     */
    public function execute(EncodedPayload $payload): EncodedPayload
    {
        $data = $payload->data();

        if (isset($data['data']['body']['trace_chain'])) {
            foreach ($data['data']['body']['trace_chain'] as $offset => $value) {
                $data['data']['body']['trace_chain'][$offset]['frames'] = self::selectFrames($value['frames']);
            }

            $payload->encode($data);
        } elseif (isset($data['data']['body']['trace']['frames'])) {
            $data['data']['body']['trace']['frames'] = self::selectFrames($data['data']['body']['trace']['frames']);
            $payload->encode($data);
        }

        return $payload;
    }

    /**
     * Removes frames from the middle of the stack trace frames. Will keep the number of frames specified by $range at
     * the start and end of the array.
     *
     * This method is also used by {@see MinBodyStrategy}.
     *
     * @param array $frames The list of stack trace frames.
     * @param int   $range  The number of frames to keep on each end of the frames array.
     *
     * @return array
     */
    public static function selectFrames(array $frames, int $range = self::FRAMES_OPTIMIZATION_RANGE): array
    {
        if (count($frames) <= $range * 2) {
            return $frames;
        }

        return array_merge(
            array_splice($frames, 0, $range),
            array_splice($frames, -$range, $range)
        );
    }

    /**
     * Returns true if the payload has a trace chain or trace frames.
     *
     * @param EncodedPayload $payload
     *
     * @return bool
     */
    public function applies(EncodedPayload $payload): bool
    {
        $payload = $payload->data();

        if (isset($payload['data']['body']['trace_chain']) ||
            isset($payload['data']['body']['trace']['frames'])) {
            return true;
        }

        return false;
    }
}
