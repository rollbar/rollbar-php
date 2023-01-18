<?php declare(strict_types=1);

namespace Rollbar\Truncation;

use Exception;
use Rollbar\Payload\EncodedPayload;

/**
 * This strategy tries to reduce the payload size to its smallest useful size. To do this exception messages are
 * truncated, exception descriptions are removed, and only the first and last trace frame is retained.
 *
 * @since 1.1.0
 */
class MinBodyStrategy extends AbstractStrategy
{
    /**
     * The maximum length of an exception message. Any message over this will be truncated.
     */
    const EXCEPTION_MESSAGE_LIMIT = 256;

    /**
     * The number of frames to keep at the beginning and end of the trace frames array.
     */
    const EXCEPTION_FRAMES_RANGE = 1;

    /**
     * Truncates the data by removing everything that is not essential to understand the report.
     *
     * @param EncodedPayload $payload
     *
     * @return EncodedPayload
     * @throws Exception If the payload encoding fails.
     */
    public function execute(EncodedPayload $payload): EncodedPayload
    {
        $data      = $payload->data();
        $modified  = false;
        $traceData = array();

        if (isset($data['data']['body']['trace'])) {
            $traceData = &$data['data']['body']['trace'];
        } elseif (isset($data['data']['body']['trace_chain'])) {
            $traceData = &$data['data']['body']['trace_chain'];
        }

        if (isset($traceData['exception'])) {
            // Delete exception description
            unset($traceData['exception']['description']);

            // Truncate exception message
            $traceData['exception']['message'] = substr(
                $traceData['exception']['message'],
                0,
                static::EXCEPTION_MESSAGE_LIMIT
            );

            $modified = true;
        }

        // Limit trace frames
        if (!empty($traceData['frames'])) {
            $traceData['frames'] = FramesStrategy::selectFrames(
                $traceData['frames'],
                static::EXCEPTION_FRAMES_RANGE
            );

            $modified = true;
        }

        if ($modified) {
            $payload->encode($data);
        }

        return $payload;
    }
}
