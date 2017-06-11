<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class Notifier implements \JsonSerializable
{
    const NAME = "rollbar-php";
    const VERSION = "1.1.1";

    public static function defaultNotifier()
    {
        return new Notifier(self::NAME, self::VERSION);
    }

    private $name;
    private $version;

    public function __construct($name, $version)
    {
        $this->setName($name);
        $this->setVersion($version);
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        Utilities::validateString($name, "name", null, false);
        $this->name = $name;
        return $this;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version)
    {
        Utilities::validateString($version, "version", null, false);
        $this->version = $version;
        return $this;
    }

    public function jsonSerialize()
    {
        return Utilities::serializeForRollbar(get_object_vars($this));
    }
}
