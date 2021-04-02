<?php namespace Rollbar\Payload;

use Rollbar\UtilitiesTrait;

class Notifier implements \Serializable
{
    const NAME = "rollbar-php";
    const VERSION = "2.1.0";

    use UtilitiesTrait;

    public static function defaultNotifier()
    {
        return new Notifier(self::NAME, self::VERSION);
    }

    public function __construct(private $name, private $version)
    {
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

    public function serialize()
    {
        $result = array(
            "name" => $this->name,
            "version" => $this->version,
        );
        
        return $this->utilities()->serializeForRollbarInternal($result);
    }
    
    public function unserialize(string $serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
