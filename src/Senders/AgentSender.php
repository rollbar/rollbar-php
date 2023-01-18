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
    public function wait(string $accessToken, int $max): void
    {
        return;
    }

    /**
     * Returns true if the access token is required by the sender to send the payload. The agent can be configured to
     * provide its own access token. But may not have its own, so we are requiring it for now. See
     * {@link https://github.com/rollbar/rollbar-php/issues/405} for more details.
     *
     * @since 4.0.0
     *
     * @return bool
     */
    public function requireAccessToken(): bool
    {
        return true;
    }

    private function loadAgentFile()
    {
        $filename       = $this->agentLogLocation . '/rollbar-relay.' . getmypid() . '.' . microtime(true) . '.rollbar';
        $this->agentLog = fopen($filename, 'a');
    }
}
