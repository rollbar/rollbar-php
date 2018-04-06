<?php namespace Rollbar\Payload;

class Notifier implements \JsonSerializable
{
    const NAME = "rollbar-php";
    const VERSION = "1.4.2";

    public static function defaultNotifier()
    {
        return new Notifier(self::NAME, self::VERSION);
    }

    private $name;
    private $version;
    private $utilities;

    public function __construct($name, $version)
    {
        $this->utilities = new \Rollbar\Utilities();
        $this->setName($name);
        $this->setVersion($version);
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    public function jsonSerialize()
    {
        $result = array(
            "name" => $this->name,
            "version" => $this->version,
        );
        return $this->utilities->serializeForRollbar($result);
    }
}
