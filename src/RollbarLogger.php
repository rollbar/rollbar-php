<?php namespace Rollbar;

use Psr\Log\AbstractLogger;
use Rollbar\Payload\Payload;

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
        $accessToken = $this->getAccessToken();
        $payload = $this->getPayload($accessToken, $level, $toLog, $context);
        $response = $this->sendOrIgnore($payload, $accessToken, $toLog);
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

    /**
     * @param Payload $payload
     * @param string $accessToken
     * @param mixed $toLog
     * @return Response
     */
    protected function sendOrIgnore($payload, $accessToken, $toLog)
    {
        if ($this->config->checkIgnored($payload, $accessToken, $toLog)) {
            return new Response(0, "Ignored");
        }

        return $this->config->send($payload, $accessToken);
    }

    protected function handleResponse($payload, $response)
    {
        $this->config->handleResponse($payload, $response);
    }
}
