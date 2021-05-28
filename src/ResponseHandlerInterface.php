<?php declare(strict_types=1);

namespace Rollbar;

use Rollbar\Payload\Payload;

interface ResponseHandlerInterface
{
    public function handleResponse(Payload $payload, mixed $response): void;
}
