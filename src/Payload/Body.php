<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class Body implements \JsonSerializable
{
    private $value;

    public function __construct(ContentInterface $value)
    {
        $this->value = $value;
    }

    public function jsonSerialize()
    {
        $overrideNames = array(
            "content" => Utilities::pascaleToCamel(get_class($value))
        );
        $obj = get_object_vars($this);
        return Utilities::serializeForRollbar($obj, $overrideNames);
    }
}
