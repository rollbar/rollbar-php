<?php declare(strict_types=1);

namespace Rollbar;

use Rollbar\Payload\Data;
use Throwable;

interface DataBuilderInterface
{
    public function makeData(string $level, Throwable|string $toLog, array $context): Data;
}
