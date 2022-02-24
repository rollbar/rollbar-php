<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;
use Rollbar\UtilitiesTrait;

class ExceptionInfo implements SerializerInterface
{
    use UtilitiesTrait;

    public function __construct(
        private string $class,
        private string $message,
        private ?string $description = null
    ) {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function setClass(string $class): self
    {
        $this->class = $class;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function serialize()
    {
        $result = array(
            "class" => $this->class,
            "message" => $this->message,
            "description" => $this->description,
        );
        
        return $this->utilities()->serializeForRollbarInternal($result);
    }
}
