<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;
use Rollbar\UtilitiesTrait;

class Notifier implements SerializerInterface
{
    const NAME = "rollbar-php";
    const VERSION = "4.0.1";

    use UtilitiesTrait;

    public static function defaultNotifier(): self
    {
        return new Notifier(self::NAME, self::VERSION);
    }

    public function __construct(private string $name, private string $version)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function serialize()
    {
        $result = array(
            "name" => $this->name,
            "version" => $this->version,
        );
        
        return $this->utilities()->serializeForRollbarInternal($result);
    }
}
