<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class Body implements \JsonSerializable
{
    private $value;

    public function __construct(ContentInterface $value)
    {
        $this->setValue($value);
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue(ContentInterface $value)
    {
        $this->value = $value;
    }

    public function jsonSerialize()
    {
        $overrideNames = array(
            "value" => $this->value->getKey()
        );
        $obj = get_object_vars($this);
        return Utilities::serializeForRollbar($obj, $overrideNames);
    }
}
