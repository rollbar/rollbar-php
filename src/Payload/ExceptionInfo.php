<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class ExceptionInfo
{
    private $class;
    private $message;
    private $description;

    public function __construct($class, $message, $description = null)
    {
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
        Utilities::validateString($class, "class", null, false);
        $this->class = $class;
        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        Utilities::validateString($message, "message", null, false);
        $this->message = $message;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        Utilities::validateString($description, "description");
        $this->description = $description;
        return $this;
    }
}
