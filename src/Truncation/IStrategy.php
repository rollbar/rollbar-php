<?php declare(strict_types=1);

namespace Rollbar\Truncation;

use \Rollbar\Payload\EncodedPayload;

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
