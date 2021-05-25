<?php declare(strict_types=1);

namespace Rollbar;

use Rollbar\Payload\Level;
use Rollbar\Payload\Payload;
use Throwable;

interface TransformerInterface
{
    public function transform(
        Payload $payload,
        Level|string $level,
        mixed $toLog,
        array $context = array ()
    ): ?Payload;
}
