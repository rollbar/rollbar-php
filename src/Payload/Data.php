<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\Defaults;
use Rollbar\SerializerInterface;
use Rollbar\UtilitiesTrait;

class Data implements SerializerInterface
{
    use UtilitiesTrait;

    private Level|string|null $level = null;
    private ?int $timestamp = null;
    private ?string $codeVersion = null;
    private ?string $platform = null;
    private ?string $language = null;
    private ?string $framework = null;
    private ?string $context = null;
    private ?Request $request = null;
    private ?Person $person = null;
    private ?Server $server = null;
    private ?array $custom = null;
    private ?string $fingerprint = null;
    private ?string $title = null;
    private ?string $uuid = null;
    private ?Notifier $notifier = null;

    public function __construct(private string $environment, private Body $body)
    {
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function setEnvironment(string $environment): self
    {
        $this->environment = $environment;
        return $this;
    }

    public function getBody(): Body
    {
        return $this->body;
    }

    public function setBody(Body $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function getLevel(): Level|string|null
    {
        return $this->level;
    }

    public function setLevel(Level|string|null $level): self
    {
        $this->level = $level;
        return $this;
    }

    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    public function setTimestamp(?int $timestamp): self
    {
        $this->timestamp = $timestamp;
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

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function setPlatform(?string $platform): self
    {
        $this->platform = $platform;
        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getFramework(): ?string
    {
        return $this->framework;
    }

    public function setFramework(?string $framework): self
    {
        $this->framework = $framework;
        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(?string $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function setRequest(?Request $request = null): self
    {
        $this->request = $request;
        return $this;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(?Person $person = null): self
    {
        $this->person = $person;
        return $this;
    }

    public function getServer(): ?Server
    {
        return $this->server;
    }

    public function setServer(?Server $server = null): self
    {
        $this->server = $server;
        return $this;
    }

    public function getCustom(): ?array
    {
        return $this->custom;
    }

    public function setCustom(?array $custom = null): self
    {
        $this->custom = $custom;
        return $this;
    }

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(?string $fingerprint): self
    {
        $this->fingerprint = $fingerprint;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getNotifier(): ?Notifier
    {
        return $this->notifier;
    }

    public function setNotifier(Notifier $notifier): self
    {
        $this->notifier = $notifier;
        return $this;
    }

    public function serialize()
    {
        $result = array(
            "environment" => $this->environment,
            "body" => $this->body,
            "level" => $this->level,
            "timestamp" => $this->timestamp,
            "code_version" => $this->codeVersion,
            "platform" => $this->platform,
            "language" => $this->language,
            "framework" => $this->framework,
            "context" => $this->context,
            "request" => $this->request,
            "person" => $this->person,
            "server" => $this->server,
            "custom" => $this->custom,
            "fingerprint" => $this->fingerprint,
            "title" => $this->title,
            "uuid" => $this->uuid,
            "notifier" => $this->notifier,
        );
        
        return $this->utilities()->serializeForRollbarInternal($result);
    }
}
