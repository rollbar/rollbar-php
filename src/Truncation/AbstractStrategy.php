<?php declare(strict_types=1);

namespace Rollbar\Truncation;

use Rollbar\Payload\EncodedPayload;

/**
 * The base for all Rollbar truncation classes.
 *
 * @since 1.1.0
 */
abstract class AbstractStrategy implements StrategyInterface
{
    public function __construct(protected Truncation $truncation)
    {
    }

    public function execute(EncodedPayload $payload): EncodedPayload
    {
        return $payload;
    }

    public function applies(EncodedPayload $payload): bool
    {
        return true;
    }
}
