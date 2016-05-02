<?php namespace Rollbar\Payload;

use Rollbar\Payload\Body;
use Rollbar\Utilities;

class Data implements \JsonSerializable
{
    private $environment;
    private $body;
    private $level;
    private $timestamp;
    private $codeVersion;
    private $platform;
    private $language;
    private $framework;
    private $context;
    private $request;
    private $person;
    private $server;
    private $custom;
    private $fingerprint;
    private $title;
    private $uuid;
    private $notifier;

    public function __construct($environment, Body $body)
    {
        Utilities::validateString($environment, "environment", null, false);
        $this->environment = $environment;

        $this->body = $body;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setLevel(Level $level)
    {
        $this->level = $level;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setTimestamp($timestamp)
    {
        Utilities::validateInteger($timestamp, "timestamp");
        $this->timestamp = $timestamp;
    }

    public function getCodeVersion()
    {
        return $this->codeVersion;
    }

    public function setCodeVersion($codeVersion)
    {
        Utilities::validateString($codeVersion, "codeVersion");
        $this->codeVersion = $codeVersion;
    }

    public function getPlatform()
    {
        return $this->platform;
    }

    public function setPlatform($platform)
    {
        Utilities::validateString($platform, "platform");
        $this->platform = $platform;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language)
    {
        Utilities::validateString($language, "language");
        $this->language = $language;
    }

    public function getFramework()
    {
        return $this->framework;
    }

    public function setFramework($framework)
    {
        Utilities::validateString($framework, "framework");
        $this->framework = $framework;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setContext($context)
    {
        Utilities::validateString($context, "context");
        $this->context = $context;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    public function getPerson()
    {
        return $this->person;
    }

    public function setPerson(Person $person)
    {
        $this->person = $person;
    }

    public function getServer()
    {
        return $this->server;
    }

    public function setServer(Server $server)
    {
        $this->server = $server;
    }

    public function getCustom()
    {
        return $this->custom;
    }

    public function setCustom(array $custom)
    {
        $this->custom = $custom;
    }

    public function getFingerprint()
    {
        return $this->fingerprint;
    }

    public function setFingerprint($fingerprint)
    {
        Utilities::validateString($fingerprint, "fingerprint");
        $this->fingerprint = $fingerprint;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        Utilities::validateString($title, "title");
        $this->title = $title;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid)
    {
        Utilities::validateString($uuid, "uuid");
        $this->uuid = $uuid;
    }

    public function getNotifier()
    {
        return $this->notifier;
    }

    public function setNotifier(Notifier $notifier)
    {
        $this->notifier = $notifier;
    }

    public function jsonSerialize()
    {
        return Utilities::serializeForRollbar(get_object_vars($this));
    }
}
