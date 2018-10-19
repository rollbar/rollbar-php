<?php namespace Rollbar;

use Psr\Log\AbstractLogger;
use Rollbar\Payload\Payload;
use Rollbar\Payload\Level;
use Rollbar\Truncation\Truncation;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Rollbar\Payload\EncodedPayload;

class RollbarLogger extends AbstractLogger
{
    private $config;
    private $levelFactory;
    private $truncation;
    private $queue;
    private $reportCount = 0;

    public function __construct(array $config)
    {
        $this->config = new Config($config);
        $this->levelFactory = new LevelFactory();
        $this->truncation = new Truncation($this->config);
        $this->queue = array();
    }
    
    public function enable()
    {
        return $this->config->enable();
    }
    
    public function disable()
    {
        return $this->config->disable();
    }
    
    public function enabled()
    {
        return $this->config->enabled();
    }
    
    public function disabled()
    {
        return $this->config->disabled();
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
        if ($this->disabled()) {
            return new Response(0, "Disabled");
        }
        
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
            $serialized = $payload->serialize($this->config->getMaxNestingDepth());
            $scrubbed = $this->scrub($serialized);
            $encoded = $this->encode($scrubbed);
            $truncated = $this->truncate($encoded);
            
            $response = $this->send($truncated, $accessToken);
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

    protected function send(\Rollbar\Payload\EncodedPayload $payload, $accessToken)
    {
        if ($this->reportCount >= $this->config->getMaxItems()) {
            return new Response(
                0,
                "Maximum number of items per request has been reached. If you " .
                "want to report more items, please use `max_items` " .
                "configuration option."
            );
        } else {
            $this->reportCount++;
        }
        
        if ($this->config->getBatched()) {
            $response = new Response(0, "Pending");
            if ($this->getQueueSize() >= $this->config->getBatchSize()) {
                $response = $this->flush();
            }
            $this->queue[] = $payload;
            return $response;
        }
        
        return $this->config->send($payload, $accessToken);
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
     * @param array $serializedPayload
     * @return array
     */
    protected function scrub(array &$serializedPayload)
    {
        $serializedPayload['data'] = $this->config->getScrubber()->scrub($serializedPayload['data']);
        return $serializedPayload;
    }
    
    /**
     * @param \Rollbar\Payload\EncodedPayload $payload
     * @return \Rollbar\Payload\EncodedPayload
     */
    protected function truncate(\Rollbar\Payload\EncodedPayload &$payload)
    {
        return $this->truncation->truncate($payload);
    }
    
    /**
     * @param array &$payload
     * @return \Rollbar\Payload\EncodedPayload
     */
    protected function encode(array &$payload)
    {
        $encoded = new EncodedPayload($payload);
        $encoded->encode();
        return $encoded;
    }
}
