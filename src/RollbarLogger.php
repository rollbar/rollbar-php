<?php namespace Rollbar;

use Psr\Log\AbstractLogger;
use Rollbar\Payload\Payload;
use Rollbar\Payload\Level;
use Rollbar\Utilities;
use Rollbar\Truncation\Truncation;

class RollbarLogger extends AbstractLogger
{
    private $config;
    private $levelFactory;
    private $truncation;

    public function __construct(array $config)
    {
        $this->config = new Config($config);
        $this->levelFactory = new LevelFactory();
        $this->truncation = new Truncation();
    }

    public function configure(array $config)
    {
        $this->config->configure($config);
    }

    public function scope(array $config)
    {
        return new RollbarLogger($this->extend($config));
    }

    public function extend(array $config)
    {
        return $this->config->extend($config);
    }

    public function log($level, $toLog, array $context = array())
    {
        if (!$this->levelFactory->isValidLevel($level)) {
            throw new \Psr\Log\InvalidArgumentException("Invalid log level '$level'.");
        }
        $isUncaught = false;
        if (array_key_exists(Utilities::IS_UNCAUGHT_KEY, $context) && $context[Utilities::IS_UNCAUGHT_KEY]) {
            $isUncaught = true;
            unset($context[Utilities::IS_UNCAUGHT_KEY]);
        }
        $accessToken = $this->getAccessToken();
        $payload = $this->getPayload($accessToken, $level, $toLog, $context);
        
        if ($this->config->checkIgnored($payload, $accessToken, $toLog, $isUncaught)) {
            $response = new Response(0, "Ignored");
        } else {
            $toSend = $this->scrub($payload);
            $toSend = $this->truncate($toSend);
            $response = $this->config->send($toSend, $accessToken);
        }
        
        $this->handleResponse($payload, $response);
        return $response;
    }

    protected function getPayload($accessToken, $level, $toLog, $context)
    {
        $data = $this->config->getRollbarData($level, $toLog, $context);
        $payload = new Payload($data, $accessToken);
        return $this->config->transform($payload, $level, $toLog, $context);
    }

    protected function getAccessToken()
    {
        return $this->config->getAccessToken();
    }
    
    public function getDataBuilder()
    {
        return $this->config->getDataBuilder();
    }

    protected function handleResponse($payload, $response)
    {
        $this->config->handleResponse($payload, $response);
    }
    
    /**
     * @param Payload $payload
     * @return array
     */
    protected function scrub(Payload $payload)
    {
        $serialized = $payload->jsonSerialize();
        $serialized['data'] = $this->config->getScrubber()->scrub($serialized['data']);
        return $serialized;
    }
    
    /**
     * @param array $payload
     * @return array
     */
    protected function truncate(array $payload)
    {
        return $this->truncation->truncate($payload);
    }
}
