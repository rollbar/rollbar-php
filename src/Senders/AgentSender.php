<?php declare(strict_types=1);



namespace Rollbar\Senders;

use Rollbar\Response;
use Rollbar\Payload\Payload;
use Rollbar\Payload\EncodedPayload;
use Rollbar\UtilitiesTrait;

class AgentSender implements SenderInterface
{
    use UtilitiesTrait;
    private $agentLog;
    private $agentLogLocation;

    public function __construct($opts)
    {
        $this->agentLogLocation = \Rollbar\Defaults::get()->agentLogLocation();
        if (array_key_exists('agentLogLocation', $opts)) {
            $this->utilities()->validateString($opts['agentLogLocation'], 'opts["agentLogLocation"]', null, false);
            $this->agentLogLocation = $opts['agentLogLocation'];
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function send(EncodedPayload $payload, string $accessToken): Response
    {
        if (empty($this->agentLog)) {
            $this->loadAgentFile();
        }
        fwrite($this->agentLog, $payload->encoded() . "\n");

        $data = $payload->data();
        $uuid = $data['data']['uuid'];
        return new Response(0, "Written to agent file", $uuid);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function sendBatch(array $batch, string $accessToken): void
    {
        if (empty($this->agentLog)) {
            $this->loadAgentFile();
        }
        foreach ($batch as $payload) {
            fwrite($this->agentLog, $payload->encoded() . "\n");
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function wait(string $accessToken, int $max)
    {
        return;
    }

    private function loadAgentFile()
    {
        $filename = $this->agentLogLocation . '/rollbar-relay.' . getmypid() . '.' . microtime(true) . '.rollbar';
        $this->agentLog = fopen($filename, 'a');
    }
    
    public function toString()
    {
        return "agent log: " . $this->agentLogLocation;
    }
}
