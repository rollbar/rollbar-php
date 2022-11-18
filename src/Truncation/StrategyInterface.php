<?php declare(strict_types=1);

namespace Rollbar\Truncation;

use Rollbar\Payload\EncodedPayload;

/**
 * The truncation strategy interface defines the method signatures for payload
 * truncation.
 *
 * @since 1.1.0
 * @since 4.0.0 Renamed from IStrategy.
 */
interface StrategyInterface
{

    public function __construct(Truncation $truncation);

    /**
     * This method will be called to truncate the payload.
     *
     * @param EncodedPayload $payload
     *
     * @return EncodedPayload
     */
    public function execute(EncodedPayload $payload): EncodedPayload;

    /**
     * This method should return true if the truncation strategy should be
     * executed on the payload.
     *
     * @param EncodedPayload $payload
     *
     * @return bool
     */
    public function applies(EncodedPayload $payload): bool;
}
