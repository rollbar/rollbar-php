<?php namespace Rollbar\Payload;

use Rollbar\UtilitiesTrait;

class ExceptionInfo implements \Serializable
{
    use UtilitiesTrait;

    public function __construct(
        private $class,
        private $message,
        private $description = null
    ) {
    }

    public function getClass()
    {
        return $this->class;
    }

    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function serialize()
    {
        $result = array(
            "class" => $this->class,
            "message" => $this->message,
            "description" => $this->description,
        );
        
        $objectHashes = \Rollbar\Utilities::getObjectHashes();
        
        return $this->utilities()->serializeForRollbar($result, null, $objectHashes);
    }
    
    public function unserialize(string $serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
