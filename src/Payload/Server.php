<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;
use Rollbar\UtilitiesTrait;

class Server implements SerializerInterface
{
    use UtilitiesTrait;

    private ?string $host = null;
    private ?string $root = null;
    private ?string $branch = null;
    private ?string $codeVersion = null;
    private array $extra = array();

    public function __construct()
    {
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(?string $host): self
    {
        $this->host = $host;
        return $this;
    }

    public function getRoot(): ?string
    {
        return $this->root;
    }

    public function setRoot(?string $root): self
    {
        $this->root = $root;
        return $this;
    }

    public function getBranch(): ?string
    {
        return $this->branch;
    }

    public function setBranch(?string $branch): self
    {
        $this->branch = $branch;
        return $this;
    }

    public function getCodeVersion(): ?string
    {
        return $this->codeVersion;
    }

    public function setCodeVersion(?string $codeVersion): self
    {
        $this->codeVersion = $codeVersion;
        return $this;
    }

    public function setExtras(array $extras): self
    {
        $this->extra = $extras;
        return $this;
    }

    public function getExtras(): array
    {
        return $this->extra;
    }

    public function setArgv(array $argv): self
    {
        $this->extra['argv'] = $argv;
        return $this;
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
        
        return $this->utilities()->serializeForRollbarInternal($result, array_keys($this->extra));
    }
}
