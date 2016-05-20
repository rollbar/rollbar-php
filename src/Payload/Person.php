<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class Person implements \JsonSerializable
{
    private $id;
    private $username;
    private $email;
    private $extra;

    public function __construct($id, $username = null, $email = null, array $extra = null)
    {
        $this->setId($id);
        $this->setUsername($username);
        $this->setEmail($email);
        $this->extra = $extra == null ? array() : $extra;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        Utilities::validateString($id, "id", null, false);
        $this->id = $id;
        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        Utilities::validateString($username, "username");
        $this->username = $username;
        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        Utilities::validateString($email, "email");
        $this->email = $email;
        return $this;
    }

    public function __get($name)
    {
        return isset($this->extra[$name]) ? $this->extra[$name] : null;
    }

    public function __set($name, $val)
    {
        $this->extra[$name] = $val;
    }

    public function jsonSerialize()
    {
        $result = get_object_vars($this);
        unset($result['extra']);
        foreach ($this->extra as $key => $val) {
            $result[$key] = $val;
        }
        return Utilities::serializeForRollbar($result, null, array_keys($this->extra));
    }
}
