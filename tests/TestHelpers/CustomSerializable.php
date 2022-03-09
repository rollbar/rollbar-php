<?php namespace Rollbar\TestHelpers;

class CustomSerializable implements \Serializable
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

    public function __serialize(): array
    {
        throw new \Exception("Not implemented");
    }

    public function __unserialize(array $data): void
    {
        throw new \Exception("Not implemented");
    }
}
