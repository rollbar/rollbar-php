<?php declare(strict_types=1);

namespace Rollbar;

use Rollbar\Payload\Payload;

interface FilterInterface
{
    public function shouldSend(Payload $payload, string $accessToken);
}
