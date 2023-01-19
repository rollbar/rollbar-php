<?php declare(strict_types=1);

namespace Rollbar\Truncation;

use \Rollbar\Payload\EncodedPayload;

/**
 * @deprecated 3.2.0 and will be renamed in 4.0.0 to Rollbar\Truncation\StrategyInterface. All internal truncation
 *             strategy classes that implement this interface (including {@see AbstractStrategy}) will use the new one
 *             in 4.0.0.
 */
interface IStrategy
{
    /**
     * @param array $payload
     * @return array
     */
    public function execute(EncodedPayload $payload);
    
    /**
     * @param array $payload
     * @return array
     */
    public function applies(EncodedPayload $payload);
}
