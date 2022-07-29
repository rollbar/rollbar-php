<?php namespace Rollbar\TestHelpers\ChildCycle;

use Rollbar\SerializerInterface;

class ChildCycleCheckSerializable implements SerializerInterface
{
    public $parent;
    
    public function __construct($parent)
    {
        $this->parent = $parent;
    }
    
    public function serialize(): array
    {
        return array(
            "parent" => \Rollbar\Utilities::serializeForRollbarInternal($this->parent)
        );
    }
}
