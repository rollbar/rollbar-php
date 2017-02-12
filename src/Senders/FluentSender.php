<?php

namespace Rollbar\Senders;

use Fluent\Logger\FluentLogger;
use Rollbar\Payload\Payload;

class FLuentSender implements SenderInterface
{
    /** @var FluentLogger  */
    private $fluentLogger = null;
    private $fluentHost = FluentLogger::DEFAULT_ADDRESS;
    private $fluentPort = FluentLogger::DEFAULT_LISTEN_PORT;
    private $fluentTag = 'rollbar';

    public function __construct($opts)
    {
        if (isset($opts['fluentHost'])) {
            $this->fluentHost = $opts['fluentHost'];
        }

        if (isset($opts['fluentPort'])) {
            $this->fluentPort = $opts['fluentPort'];
        }

        if (isset($opts['fluentTag'])) {
            $this->fluentTag = $opts['fluentTag'];
        }
    }

    public function send(Payload $payload, $accessToken)
    {
        if (empty($this->fluentLogger)) {
            $this->loadFluentLogger();
        }

        $this->fluentLogger->post($this->fluentTag, $payload->jsonSerialize());
    }

    private function loadFluentLogger()
    {
        $this->fluentLogger = new FluentLogger($this->fluentHost, $this->fluentPort);
    }
}
