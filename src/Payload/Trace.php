<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class Trace extends ContentInterface
{
    private $frames;
    private $exception;

    public function __construct(array $frames, ExceptionInfo $exception)
    {
        $this->frames = $frames;
        $this->exception = $exception;
    }

    public function getFrames()
    {
        return $this->frames;
    }

    public function getException()
    {
        return $this->exception;
    }

    public function jsonSerialize()
    {
        return Utilities::serializeForRollbar(get_object_vars($this));
    }
}
