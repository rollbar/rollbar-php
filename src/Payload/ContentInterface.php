<?php namespace Rollbar\Payload;

abstract class ContentInterface implements \JsonSerializable
{
    public function getKey()
    {
        $name = get_class($this);
        return str_replace("Rollbar\\Payload\\", "", $name);
    }
}
