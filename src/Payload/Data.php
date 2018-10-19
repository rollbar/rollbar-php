<?php namespace Rollbar\Payload;

use Rollbar\Defaults;

class Data implements \Serializable
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
    private $utilities;

    public function __construct($environment, Body $body)
    {
        $this->utilities = new \Rollbar\Utilities();
        $this->setEnvironment($environment);
        $this->setBody($body);
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function setEnvironment($environment)
    {
        $this->environment = $environment;
        return $this;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody(Body $body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return Level
     */
    public function getLevel()
    {
        return $this->level;
    }

    public function setLevel($level)
    {
        $this->level = $level;
        return $this;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
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

    public function getPlatform()
    {
        return $this->platform;
    }

    public function setPlatform($platform)
    {
        $this->platform = $platform;
        return $this;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    public function getFramework()
    {
        return $this->framework;
    }

    public function setFramework($framework)
    {
        $this->framework = $framework;
        return $this;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function setRequest(Request $request = null)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    public function setPerson(Person $person = null)
    {
        $this->person = $person;
        return $this;
    }

    /**
     * @return Server
     */
    public function getServer()
    {
        return $this->server;
    }

    public function setServer(Server $server = null)
    {
        $this->server = $server;
        return $this;
    }

    public function getCustom()
    {
        return $this->custom;
    }

    public function setCustom(array $custom = null)
    {
        $this->custom = $custom;
        return $this;
    }

    public function getFingerprint()
    {
        return $this->fingerprint;
    }

    public function setFingerprint($fingerprint)
    {
        $this->fingerprint = $fingerprint;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getNotifier()
    {
        return $this->notifier;
    }

    public function setNotifier(Notifier $notifier)
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
        
        $objectHashes = \Rollbar\Utilities::getObjectHashes();
        
        return $this->utilities->serializeForRollbar($result, null, $objectHashes);
    }
    
    public function unserialize($serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
