<?php namespace Rollbar\Payload;

class Frame implements \Serializable
{
    private $filename;
    private $lineno;
    private $colno;
    private $method;
    private $code;
    private $context;
    private $args;
    private $utilities;

    public function __construct($filename)
    {
        $this->utilities = new \Rollbar\Utilities();
        $this->setFilename($filename);
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    public function getLineno()
    {
        return $this->lineno;
    }

    public function setLineno($lineno)
    {
        $this->lineno = $lineno;
        return $this;
    }

    public function getColno()
    {
        return $this->colno;
    }

    public function setColno($colno)
    {
        $this->colno = $colno;
        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setContext(Context $context)
    {
        $this->context = $context;
        return $this;
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function setArgs(array $args)
    {
        $this->args = $args;
        return $this;
    }

    public function serialize()
    {
        $result = array(
            "filename" => $this->filename,
            "lineno" => $this->lineno,
            "colno" => $this->colno,
            "method" => $this->method,
            "code" => $this->code,
            "context" => $this->context,
            "args" => $this->args
        );
        
        $objectHashes = \Rollbar\Utilities::getObjectHashes();
        
        return $this->utilities->serializeForRollbar($result, null, $objectHashes);
    }
    
    public function unserialize($serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
