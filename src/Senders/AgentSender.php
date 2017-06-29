<?php

namespace Rollbar\Senders;

use Rollbar\Payload\Payload;

class AgentSender implements SenderInterface
{
    private $utilities;
    private $agentLog;
    private $agentLogLocation = '/var/tmp';

    public function __construct($opts)
    {
        $this->utilities = new \Rollbar\Utilities();
        if (array_key_exists('agentLogLocation', $opts)) {
            $this->utilities->validateString($opts['agentLogLocation'], 'opts["agentLogLocation"]', null, false);
            $this->agentLogLocation = $opts['agentLogLocation'];
        }
    }

    public function send($scrubbedPayload, $accessToken)
    {
        if (empty($this->agentLog)) {
            $this->loadAgentFile();
        }
        fwrite($this->agentLog, json_encode($scrubbedPayload) . "\n");
    }

    public function sendBatch($batch, $accessToken)
    {
        if (empty($this->agentLog)) {
            $this->loadAgentFile();
        }
        foreach ($batch as $payload) {
            fwrite($this->agentLog, json_encode($payload) . "\n");
        }
    }

    private function loadAgentFile()
    {
        $filename = $this->agentLogLocation . '/rollbar-relay.' . getmypid() . '.' . microtime(true) . '.rollbar';
        $this->agentLog = fopen($filename, 'a');
    }
}
