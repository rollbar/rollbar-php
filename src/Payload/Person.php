<?php namespace Rollbar\Payload;

/**
 * Suppress PHPMD.ShortVariable for this class, since using property $id is
 * intended.
 *
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class Person implements \Serializable
{
    private $id;
    private $username;
    private $email;
    private $extra;
    private $utilities;

    public function __construct($id, $username = null, $email = null, array $extra = null)
    {
        $this->utilities = new \Rollbar\Utilities();
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
        $this->id = $id;
        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
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

    public function serialize()
    {
        $result = array(
            "id" => $this->id,
            "username" => $this->username,
            "email" => $this->email,
        );
        foreach ($this->extra as $key => $val) {
            $result[$key] = $val;
        }
        
        $objectHashes = \Rollbar\Utilities::getObjectHashes();
        
        return $this->utilities->serializeForRollbar($result, array_keys($this->extra), $objectHashes);
    }
    
    public function unserialize($serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
