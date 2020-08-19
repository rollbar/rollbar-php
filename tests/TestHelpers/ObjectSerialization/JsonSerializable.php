<?php

namespace Rollbar\TestHelpers\ObjectSerialization;

class JsonSerializable implements \JsonSerializable
{
    public function jsonSerialize(): array
    {
        return array('jsonSerialize');
    }
}
