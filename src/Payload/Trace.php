<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class Trace extends ContentInterface
{
    private $frames;
    private $exception;

    public function __construct(array $frames, ExceptionInfo $exception)
    {
        $this->setFrames($frames);
        $this->exception = $exception;
    }

    private function setFrames(array $frames)
    {
        foreach ($frames as $frame) {
            if (!$frame instanceof Frame) {
                throw new \InvalidArgumentException("\$frames must all be Rollbar\Payload\Frames");
            }
        }
        $this->frames = $frames;
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
