<?php namespace Rollbar\Payload;

class Message implements ContentInterface
{
    private $body;
    private $backtrace;
    private $utilities;

    public function __construct(
        $body,
        $backtrace = null
    ) {
        $this->utilities = new \Rollbar\Utilities();
        $this->setBody($body);
        $this->setBacktrace($backtrace);
    }

    public function getKey()
    {
        return 'message';
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
    
    public function getBacktrace()
    {
        return $this->backtrace;
    }

    public function setBacktrace($backtrace)
    {
        $this->backtrace = $backtrace;
        return $this;
    }

    public function serialize()
    {
        $toSerialize = array(
            "body" => $this->getBody(),
            "backtrace" => $this->getBacktrace()
        );
        return $this->utilities->serializeForRollbar($toSerialize);
    }
    
    public function unserialize($serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
