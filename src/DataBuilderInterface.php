<?php namespace Rollbar;

interface DataBuilderInterface
{
    public function makeData($level, $toLog, $context);
}
