<?php

namespace Rollbar\Senders;

use Rollbar\Payload\Payload;

class ErrorlogSender implements SenderInterface
{
    const OPERATING_SYSTEM = 0;
    const SAPI = 4;

    protected $messageType;
    protected $expandNewlines;

    public function __construct($opts)
    {
        $this->messageType = isset($opts['messageType']) ? $opts['messageType'] : self::OPERATING_SYSTEM;

        if ($this->messageType && false === in_array($this->messageType, [self::OPERATING_SYSTEM, self::SAPI])) {
            $message = sprintf('The given message type "%s" is not supported', print_r($this->messageType, true));
            throw new \InvalidArgumentException($message);
        }
    }

    public function send(Payload $payload, $accessToken)
    {
        error_log((string) json_encode($payload->jsonSerialize()) . "\n", $this->messageType);
    }
}
