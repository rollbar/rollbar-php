<?php declare(strict_types=1);

namespace Rollbar\Truncation;

use Exception;
use Rollbar\Payload\EncodedPayload;

/**
 * The strings strategy loops over the thresholds and truncates every string in the payload that exceeds the threshold
 * until the payload is no longer to large.
 *
 * @since 1.1.0
 */
class StringsStrategy extends AbstractStrategy
{
    /**
     * Returns the thresholds in order of largest to smallest.
     *
     * @return int[]
     */
    public static function getThresholds(): array
    {
        return array(1024, 512, 256);
    }

    /**
     * Truncates every string that is longer than the threshold until the payload is small enough.
     *
     * @param EncodedPayload $payload The payload that needs to be truncated.
     *
     * @return EncodedPayload The truncated payload.
     * @throws Exception If the JSON encoding fails.
     */
    public function execute(EncodedPayload $payload): EncodedPayload
    {
        $data     = $payload->data();
        $modified = false;

        foreach (static::getThresholds() as $threshold) {
            if (!$this->truncation->needsTruncating($payload)) {
                break;
            }

            if ($this->traverse($data, $threshold, $payload)) {
                $modified = true;
            }
        }

        if ($modified) {
            $payload->encode($data);
        }

        return $payload;
    }

    /**
     * Traverse recursively reduces the length of each string to the max length of $threshold. The strings in the $data
     * array are truncated in place and not returned.
     *
     * @param array          $data      An array that may contain strings needing to be truncated.
     * @param int            $threshold The maximum length string may be before it is truncated.
     * @param EncodedPayload $payload   The payload that may need to be truncated.
     *
     * @return bool Returns true if the data was modified.
     */
    protected function traverse(array &$data, int $threshold, EncodedPayload $payload): bool
    {
        $modified = false;

        foreach ($data as &$value) {
            if (is_array($value)) {
                if ($this->traverse($value, $threshold, $payload)) {
                    $modified = true;
                }
                continue;
            }
            if (is_string($value) && (($strlen = strlen($value)) > $threshold)) {
                $value    = substr($value, 0, $threshold);
                $modified = true;
                $payload->decreaseSize($strlen - $threshold);
            }
        }

        return $modified;
    }
}
