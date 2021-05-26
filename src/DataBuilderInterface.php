<?php declare(strict_types=1);

namespace Rollbar;

interface DataBuilderInterface
{
    public function makeData($level, $toLog, $context);
}
