<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;

class TraceChain implements SerializerInterface
{
    public function __construct(private array $traces)
    {
    }

    public function getKey(): string
    {
        return 'trace_chain';
    }

    public function getTraces(): array
    {
        return $this->traces;
    }

    public function setTraces(array $traces): self
    {
        $this->traces = $traces;
        return $this;
    }

    public function serialize()
    {
        $mapValue = function ($value) {
            if ($value instanceof \Serializable || $value instanceof SerializerInterface) {
                return $value->serialize();
            }
            return $value;
        };
        return array_map($mapValue, $this->traces);
    }
}
