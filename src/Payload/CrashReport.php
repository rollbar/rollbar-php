<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class CrashReport extends ContentInterface
{
    private $raw;

    public function __construct($raw)
    {
        $this->setRaw($raw);
    }

    public function getRaw()
    {
        return $this->raw;
    }

    public function setRaw($raw)
    {
        Utilities::validateString($raw, "raw", null, false);
        $this->raw = $raw;
        return $this;
    }

    public function jsonSerialize()
    {
        return Utilities::serializeForRollbar(get_object_vars($this));
    }
}
