<?php

namespace Rollbar\TestHelpers\ObjectSerialization;

class Serializable implements \Serializable
{
    public function serialize(): string
    {
        return 'serializable';
    }

    public function unserialize($serialized): void
    {
    }
}
