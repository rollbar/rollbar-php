<?php namespace Rollbar\Payload;

use Rollbar\UtilitiesTrait;

class Trace implements ContentInterface
{
    use UtilitiesTrait;

    public function __construct(
        private array $frames,
        private ExceptionInfo $exception
    ) {
    }

    public function getKey()
    {
        return 'trace';
    }

    public function getFrames()
    {
        return $this->frames;
    }

    public function setFrames(array $frames)
    {
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

    public function serialize()
    {
        $result = array(
            "frames" => $this->frames,
            "exception" => $this->exception,
        );
        return $this->utilities()->serializeForRollbar($result);
    }
    
    public function unserialize($serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
