<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class Server implements \JsonSerializable
{
    private $host;
    private $root;
    private $branch;
    private $codeVersion;
    private $extra = array();

    public function getHost()
    {
        return $this->host;
    }

    public function setHost($host)
    {
        Utilities::validateString($host, "host");
        $this->host = $host;
        return $this;
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function setRoot($root)
    {
        Utilities::validateString($root, "root");
        $this->root = $root;
        return $this;
    }

    public function getBranch()
    {
        return $this->branch;
    }

    public function setBranch($branch)
    {
        Utilities::validateString($branch, "branch");
        $this->branch = $branch;
        return $this;
    }

    public function getCodeVersion()
    {
        return $this->codeVersion;
    }

    public function setCodeVersion($codeVersion)
    {
        Utilities::validateString($codeVersion, "codeVersion");
        $this->codeVersion = $codeVersion;
        return $this;
    }

    public function __get($key)
    {
        return isset($this->extra[$key]) ? $this->extra[$key] : null;
    }

    public function __set($key, $val)
    {
        $this->extra[$key] = $val;
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
