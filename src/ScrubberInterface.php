<?php declare(strict_types=1);

namespace Rollbar;

/**
 * The scrubber interface allows a custom scrubber to be created that removes sensitive data and PII from the payload
 * before it is sent to the Rollbar service. Optionally, the class implementing the interface may include a constructor
 * that accepts a single argument for the configs array.
 */
interface ScrubberInterface
{
    /**
     * The scrub method is called to clean PII from the exception or the log message payload.
     *
     * @param array  $data        The array serialized data to be scrubbed.
     * @param string $replacement The replacement value to use for anything that is deemed sensitive.
     *
     * @return array
     */
    public function scrub(array &$data, string $replacement): array;
}
