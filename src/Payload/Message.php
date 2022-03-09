<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\UtilitiesTrait;

class Message implements ContentInterface
{
    use UtilitiesTrait;

    public function __construct(
        private string $body,
        private ?array $backtrace = null
    ) {
    }

    public function getKey(): string
    {
        return 'message';
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }
    
    public function getBacktrace(): ?array
    {
        return $this->backtrace;
    }

    public function setBacktrace(?array $backtrace): self
    {
        $this->backtrace = $backtrace;
        return $this;
    }

    public function serialize()
    {
        $toSerialize = array(
            "body" => $this->getBody(),
            "backtrace" => $this->getBacktrace()
        );
        return $this->utilities()->serializeForRollbar($toSerialize);
    }
}
