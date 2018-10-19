<?php namespace Rollbar\Payload;

class Server implements \Serializable
{
    private $host;
    private $root;
    private $branch;
    private $codeVersion;
    private $extra = array();
    private $utilities;

    public function __construct()
    {
        $this->utilities = new \Rollbar\Utilities();
    }

    public function getHost()
    {
        return $this->host;
    }

    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function setRoot($root)
    {
        $this->root = $root;
        return $this;
    }

    public function getBranch()
    {
        return $this->branch;
    }

    public function setBranch($branch)
    {
        $this->branch = $branch;
        return $this;
    }

    public function getCodeVersion()
    {
        return $this->codeVersion;
    }

    public function setCodeVersion($codeVersion)
    {
        $this->codeVersion = $codeVersion;
        return $this;
    }

    public function setExtras($extras)
    {
        $this->extra = $extras;
    }

    public function getExtras()
    {
        return $this->extra;
    }

    public function setArgv($argv)
    {
        $this->extra['argv'] = $argv;
    }

    public function serialize()
    {
        $result = array(
            "host" => $this->host,
            "root" => $this->root,
            "branch" => $this->branch,
            "code_version" => $this->codeVersion,
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
