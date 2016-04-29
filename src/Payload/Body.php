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
        $returnVal = array();

        $key = Utilities::pascaleToCamel(get_class($value));
        $returnVal[key] = $value;

        return $returnVal;
    }
}
