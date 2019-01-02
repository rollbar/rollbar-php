<?php namespace Rollbar\Payload;

class Body implements \Serializable
{
    /**
     * @var ContentInterface
     */
    private $value;
    private $utilities;
    private $extra;

    public function __construct(ContentInterface $value, array $extra = array())
    {
        $this->utilities = new \Rollbar\Utilities();
        $this->setValue($value);
        $this->setExtra($extra);
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
    
    public function setExtra(array $extra)
    {
        $this->extra = $extra;
        return $this;
    }
    
    public function getExtra()
    {
        return $this->extra;
    }

    public function serialize()
    {
        $result = array();
        $result[$this->value->getKey()] = $this->value;
        
        if (!empty($this->extra)) {
            $result['extra'] = $this->extra;
        }
        
        $objectHashes = \Rollbar\Utilities::getObjectHashes();
        
        return $this->utilities->serializeForRollbar(
            $result,
            array('extra'),
            $objectHashes
        );
    }
    
    public function unserialize($serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
