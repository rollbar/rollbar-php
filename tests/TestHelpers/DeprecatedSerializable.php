<?php namespace Rollbar\TestHelpers;

class DeprecatedSerializable implements \Serializable
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function serialize()
    {
        return $this->data;
    }

    public function unserialize(string $data)
    {
        throw new \Exception("Not implemented");
    }
}
