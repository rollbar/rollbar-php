<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;
use Rollbar\UtilitiesTrait;

class Body implements SerializerInterface
{
    use UtilitiesTrait;

    public function __construct(
        private ContentInterface $value,
        private array $extra = array()
    ) {
    }

    public function getValue(): ContentInterface
    {
        return $this->value;
    }

    public function setValue(ContentInterface $value): self
    {
        $this->value = $value;
        return $this;
    }
    
    public function setExtra(array $extra): self
    {
        $this->extra = $extra;
        return $this;
    }
    
    public function getExtra(): array
    {
        return $this->extra;
    }

    public function serialize()
    {
        $result = array();
        $result[$this->value->getKey()] = $this->value;
        
        if (!empty($this->extra)) {
            $result['extra'] = $this->extra;
        }
        
        return $this->utilities()->serializeForRollbarInternal($result, array('extra'));
    }
}
