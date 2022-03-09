<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;
use Rollbar\UtilitiesTrait;

/**
 * Suppress PHPMD.ShortVariable for this class, since using property $id is
 * intended.
 *
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class Person implements SerializerInterface
{
    use UtilitiesTrait;

    public function __construct(
        private string $id,
        private ?string $username = null,
        private ?string $email = null,
        private array $extra = []
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function __get($name)
    {
        return $this->extra[$name] ?? null;
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
        
        return $this->utilities()->serializeForRollbarInternal($result, array_keys($this->extra));
    }
}
