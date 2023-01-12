<?php declare(strict_types=1);

namespace Rollbar;

use Rollbar\Payload\Level;
use Rollbar\Payload\Payload;

/**
 * The scrubber interface allows changes to be made to the error or log {@see Payload} before it is checked to see if
 * it will be ignored, is serialized, scrubbed of PII or many other processes. The transform method is executed
 * directly after the payload is created.
 *
 * Note: transforms are only applied to payloads that were created through calling one of the {@see Rollbar} or
 * {@see RollbarLogger} logging methods or for errors and exceptions that where caught by Rollbar.
 *
 * An optional constructor can be included in the implementing class. The constructor should accept a single array
 * argument. The value of the argument will be the value of `transformerOptions` from the configs array, or an empty
 * array if `transformerOptions` does not exist.
 */
interface TransformerInterface
{
    /**
     * The transform method allows changes to be made to a {@see Payload} just after it is created and before it is
     * passed to anything else in the standard logging and error catching process.
     *
     * @param Payload      $payload The payload that has just been created.
     * @param Level|string $level   The severity of the log message or error.
     * @param mixed        $toLog   The error or message that is being logged.
     * @param array        $context Additional context data that may be sent with the message. In accordance with
     *                              PSR-3 an exception may be in $context['exception'] not $toLog.
     *
     * @return Payload
     */
    public function transform(
        Payload $payload,
        Level|string $level,
        mixed $toLog,
        array $context = array()
    ): Payload;
}
