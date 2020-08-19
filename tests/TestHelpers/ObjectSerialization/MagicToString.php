<?php

namespace Rollbar\TestHelpers\ObjectSerialization;

class MagicToString
{
    public function __toString(): string
    {
        return '__toString';
    }
}
