<?php

namespace Rollbar\Senders;

use Rollbar\Payload\Payload;
use Rollbar\Utilities;

class AgentSender implements SenderInterface
{
    private $agentLog;
    private $agentLogLocation = '/var/tmp';

    public function __construct($opts)
    {
        if (array_key_exists('agentLogLocation', $opts)) {
            Utilities::validateString($opts['agentLogLocation'], 'opts["agentLogLocation"]', null, false);
            $this->agentLogLocation = $opts['agentLogLocation'];
        }
    }

    public function send(Payload $payload, $accessToken)
    {
        if (empty($this->agentLog)) {
            $this->loadAgentFile();
        }
        fwrite($this->agentLog, json_encode($payload->jsonSerialize()) . "\n");
    }

    private function loadAgentFile()
    {
        $filename = $this->agentLogLocation . '/rollbar-relay.' . getmypid() . '.' . microtime(true) . '.rollbar';
        $this->agentLog = fopen($filename, 'a');
    }
}
