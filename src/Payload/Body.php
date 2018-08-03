<?php namespace Rollbar\Payload;

class Body implements \Serializable
{
    /**
     * @var ContentInterface
     */
    private $value;
    private $utilities;

    public function __construct(ContentInterface $value)
    {
        $this->utilities = new \Rollbar\Utilities();
        $this->setValue($value);
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue(ContentInterface $value)
    {
        $this->value = $value;
        return $this;
    }

    public function serialize()
    {
        $result = array();
        $result[$this->value->getKey()] = $this->value;
        return $this->utilities->serializeForRollbar($result);
    }
    
    public function unserialize($serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
