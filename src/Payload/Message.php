<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class Message extends ContentInterface
{
    private $body;
    private $extra;

    public function __construct($body, array $extra = null)
    {
        $this->setBody($body);
        $this->extra = $extra == null ? array() : $extra;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    public function __set($key, $val)
    {
        if ($key == "body") {
            $this->setBody($val);
        } else {
            $this->extra[$key] = $val;
        }
    }

    public function __get($key)
    {
        if ($key == "body") {
            return $this->getBody();
        } else {
            return $this->extra[$key];
        }
    }

    public function jsonSerialize()
    {
        $toSerialize = array("body" => $this->getBody());
        foreach ($this->extra as $key => $value) {
            $toSerialize[$key] = $value;
        }
        return Utilities::serializeForRollbar($toSerialize);
    }
}
