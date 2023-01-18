<?php declare(strict_types=1);

namespace Rollbar\Truncation;

use Rollbar\Payload\EncodedPayload;

/**
 * The raw strategy does not truncate the payload at all.
 *
 * @since 1.1.0
 */
class RawStrategy extends AbstractStrategy
{
    public function execute(EncodedPayload $payload): EncodedPayload
    {
        return $payload;
    }
}
