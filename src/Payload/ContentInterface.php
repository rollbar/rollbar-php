<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

abstract class ContentInterface implements \JsonSerializable
{
    public function getKey()
    {
        $className = str_replace("Rollbar\\Payload\\", "", get_class($this));
        return Utilities::pascalToCamel($className);
    }
}
