<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class Trace extends ContentInterface
{
    private $frames;
    private $exception;

    public function __construct(array $frames, ExceptionInfo $exception)
    {
        $this->setFrames($frames);
        $this->setException($exception);
    }

    public function getFrames()
    {
        return $this->frames;
    }

    public function setFrames(array $frames)
    {
        foreach ($frames as $frame) {
            if (!$frame instanceof Frame) {
                throw new \InvalidArgumentException("\$frames must all be Rollbar\Payload\Frames");
            }
        }
        $this->frames = $frames;
        return $this;
    }

    public function getException()
    {
        return $this->exception;
    }

    public function setException(ExceptionInfo $exception)
    {
        $this->exception = $exception;
        return $this;
    }

    public function jsonSerialize()
    {
        return Utilities::serializeForRollbar(get_object_vars($this));
    }
}
