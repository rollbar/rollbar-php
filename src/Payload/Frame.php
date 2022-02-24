<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;
use Rollbar\UtilitiesTrait;

/**
 * Represents a stack trace frame, as returned by debug_backtrace. Note that
 * in the Zend engine, Throwable::getTrace() is a thin wrapper around
 * debug_backtrace.
 */
class Frame implements SerializerInterface
{
    use UtilitiesTrait;

    private ?int $lineno = null;
    private ?int $colno = null;
    private ?string $method = null;
    private ?string $code = null;
    private ?Context $context = null;
    private ?array $args = null;

    public function __construct(private ?string $filename)
    {
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    public function getLineno(): ?int
    {
        return $this->lineno;
    }

    public function setLineno(?int $lineno): self
    {
        $this->lineno = $lineno;
        return $this;
    }

    public function getColno(): ?int
    {
        return $this->colno;
    }

    public function setColno(?int $colno): self
    {
        $this->colno = $colno;
        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getContext(): ?Context
    {
        return $this->context;
    }

    public function setContext(Context $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getArgs(): ?array
    {
        return $this->args;
    }

    public function setArgs(array $args): self
    {
        $this->args = $args;
        return $this;
    }

    public function serialize()
    {
        $result = array(
            "filename" => $this->filename,
            "lineno" => $this->lineno,
            "colno" => $this->colno,
            "method" => $this->method,
            "code" => $this->code,
            "context" => $this->context,
            "args" => $this->args
        );
        
        return $this->utilities()->serializeForRollbarInternal($result);
    }
}
