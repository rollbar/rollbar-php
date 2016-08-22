<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class Frame implements \JsonSerializable
{
    private $filename;
    private $lineno;
    private $colno;
    private $method;
    private $code;
    private $context;
    private $args;
    private $kwargs;

    public function __construct($filename)
    {
        $this->setFilename($filename);
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function setFilename($filename)
    {
        Utilities::validateString($filename, "filename", null, false);
        $this->filename = $filename;
        return $this;
    }

    public function getLineno()
    {
        return $this->lineno;
    }

    public function setLineno($lineno)
    {
        Utilities::validateInteger($lineno, "lineno");
        $this->lineno = $lineno;
        return $this;
    }

    public function getColno()
    {
        return $this->colno;
    }

    public function setColno($colno)
    {
        Utilities::validateInteger($colno, "colno");
        $this->colno = $colno;
        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod($method)
    {
        Utilities::validateString($method, "method");
        $this->method = $method;
        return $this;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        Utilities::validateString($code, "code");
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

    public function getKwargs()
    {
        return $this->kwargs;
    }

    public function setKwargs(array $kwargs)
    {
        $this->kwargs = $kwargs;
        return $this;
    }


    public function jsonSerialize()
    {
        return Utilities::serializeForRollbar(get_object_vars($this));
    }
}
