<?php namespace Rollbar\Payload;

class ExceptionInfo implements \JsonSerializable
{
    private $class;
    private $message;
    private $description;
    private $utilities;

    public function __construct($class, $message, $description = null)
    {
        $this->utilities = new \Rollbar\Utilities();
        $this->setClass($class);
        $this->setMessage($message);
        $this->setDescription($description);
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

    public function jsonSerialize()
    {
        $result = array(
            "class" => $this->class,
            "message" => $this->message,
            "description" => $this->description,
        );
        return $this->utilities->serializeForRollbar($result);
    }
}
