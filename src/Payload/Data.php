<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class Data implements \JsonSerializable
{
    private $body;

    public function jsonSerialize()
    {
        return Utilities::serializeForRollbar(get_object_vars($this));
    }
}
