<?php namespace Rollbar;

use Psr\Log\AbstractLogger;
use Rollbar\Payload\Payload;
use Rollbar\Payload\Level;
use Rollbar\Utilities;

class RollbarLogger extends AbstractLogger
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = new Config($config);
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
        if (Level::fromName($level) === null) {
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
        $serialized['data'] = $this->config->getDataBuilder()->scrub($serialized['data']);
        return $serialized;
    }
    
    /**
     * @param array $payload
     * @return array
     */
    protected function truncate(array $payload)
    {
        return $this->config->getDataBuilder()->truncate($payload);
    }
}
