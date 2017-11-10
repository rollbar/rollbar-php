<?php namespace Rollbar;

use Psr\Log\AbstractLogger;
use Rollbar\Payload\Payload;
use Rollbar\Payload\Level;
use Rollbar\Truncation\Truncation;

class RollbarLogger extends AbstractLogger
{
    private $config;
    private $levelFactory;
    private $truncation;
    private $queue;

    public function __construct(array $config)
    {
        $this->config = new Config($config);
        $this->levelFactory = new LevelFactory();
        $this->truncation = new Truncation();
        $this->queue = array();
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
    
    public function addCustom($key, $data)
    {
        $this->config->addCustom($key, $data);
    }
    
    public function removeCustom($key)
    {
        $this->config->removeCustom($key);
    }
    
    public function getCustom()
    {
        return $this->config->getCustom();
    }

    public function log($level, $toLog, array $context = array(), $isUncaught = false)
    {
        if (!$this->levelFactory->isValidLevel($level)) {
            throw new \Psr\Log\InvalidArgumentException("Invalid log level '$level'.");
        }
        if ($this->config->internalCheckIgnored($level, $toLog)) {
            return new Response(0, "Ignored");
        }
        $accessToken = $this->getAccessToken();
        $payload = $this->getPayload($accessToken, $level, $toLog, $context);
        
        if ($this->config->checkIgnored($payload, $accessToken, $toLog, $isUncaught)) {
            $response = new Response(0, "Ignored");
        } else {
            $toSend = $this->scrub($payload);
            $toSend = $this->truncate($toSend);
            $response = $this->send($toSend, $accessToken);
        }
        
        $this->handleResponse($payload, $response);
        return $response;
    }

    public function flush()
    {
        if ($this->getQueueSize() > 0) {
            $batch = $this->queue;
            $this->queue = array();
            return $this->config->sendBatch($batch, $this->getAccessToken());
        }
        return new Response(0, "Queue empty");
    }

    public function flushAndWait()
    {
        $this->flush();
        $this->config->wait($this->getAccessToken());
    }

    public function shouldIgnoreError($errno)
    {
        return $this->config->shouldIgnoreError($errno);
    }

    public function getQueueSize()
    {
        return count($this->queue);
    }

    protected function send($toSend, $accessToken)
    {
        if ($this->config->getBatched()) {
            $response = new Response(0, "Pending");
            if ($this->getQueueSize() >= $this->config->getBatchSize()) {
                $response = $this->flush();
            }
            $this->queue[] = $toSend;
            return $response;
        }
        return $this->config->send($toSend, $accessToken);
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
