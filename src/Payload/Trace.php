<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\UtilitiesTrait;

class Trace implements ContentInterface
{
    use UtilitiesTrait;

    public function __construct(
        private array $frames,
        private ExceptionInfo $exception
    ) {
    }

    public function getKey(): string
    {
        return 'trace';
    }

    public function getFrames(): array
    {
        return $this->frames;
    }

    public function setFrames(array $frames): self
    {
        $this->frames = $frames;
        return $this;
    }

    public function getException(): ExceptionInfo
    {
        return $this->exception;
    }

    public function setException(ExceptionInfo $exception): self
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
}
