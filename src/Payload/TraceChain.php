<?php namespace Rollbar\Payload;

class TraceChain implements ContentInterface
{
    private $traces;

    public function __construct(array $traces)
    {
        $this->setTraces($traces);
    }

    public function getKey()
    {
        return 'trace_chain';
    }

    public function getTraces()
    {
        return $this->traces;
    }

    public function setTraces($traces)
    {
        if (count($traces) < 1) {
            throw new \InvalidArgumentException('$traces must contain at least 1 Trace');
        }
        foreach ($traces as $trace) {
            if (!$trace instanceof Trace) {
                throw new \InvalidArgumentException('$traces must all be Trace instances');
            }
        }
        $this->traces = $traces;
        return $this;
    }

    public function jsonSerialize()
    {
        $mapValue = function ($value) {
            if ($value instanceof \JsonSerializable) {
                return $value->jsonSerialize();
            }
            return $value;
        };
        return array_map($mapValue, $this->traces);
    }
}
