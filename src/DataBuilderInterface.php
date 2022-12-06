<?php declare(strict_types=1);

namespace Rollbar;

use Rollbar\Payload\Data;
use Stringable;
use Throwable;

interface DataBuilderInterface
{
    public function makeData(string $level, Throwable|string|Stringable $toLog, array $context): Data;
}
