<?php namespace Rollbar\Payload;

class Notifier implements \Serializable
{
    const NAME = "rollbar-php";
    const VERSION = "2.1.0";

    public static function defaultNotifier()
    {
        return new Notifier(self::NAME, self::VERSION);
    }

    private $utilities;

    public function __construct(private $name, private $version)
    {
        $this->utilities = new \Rollbar\Utilities();
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
        
        $objectHashes = \Rollbar\Utilities::getObjectHashes();
        
        return $this->utilities->serializeForRollbar($result, null, $objectHashes);
    }
    
    public function unserialize(string $serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
