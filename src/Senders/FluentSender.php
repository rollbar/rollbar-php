<?php declare(strict_types=1);



namespace Rollbar\Senders;

use Fluent\Logger\FluentLogger;
use Rollbar\Response;
use Rollbar\Payload\EncodedPayload;
use Rollbar\UtilitiesTrait;

class FluentSender implements SenderInterface
{
    use UtilitiesTrait;

    /**
     * @var FluentLogger FluentLogger instance
     */
    private $fluentLogger = null;

    /**
     * @var string IP of the fluentd host
     */
    private $fluentHost;

    /**
     * @var int Port of the fluentd instance listening on
     */
    private $fluentPort = FluentLogger::DEFAULT_LISTEN_PORT;

    /**
     * @var string Tag that will be used for filter and match sections in fluentd
     */
    private $fluentTag = 'rollbar';


    /**
     * FluentSender constructor.
     * @param $opts array containing options for the sender
     */
    public function __construct($opts)
    {
        $this->fluentHost = \Rollbar\Defaults::get()->fluentHost();
        $this->fluentPort = \Rollbar\Defaults::get()->fluentPort();
        $this->fluentTag = \Rollbar\Defaults::get()->fluentTag();
        
        if (isset($opts['fluentHost'])) {
            $this->utilities()->validateString($opts['fluentHost'], 'opts["fluentHost"]', null, false);
            $this->fluentHost = $opts['fluentHost'];
        }

        if (isset($opts['fluentPort'])) {
            $this->utilities()->validateInteger($opts['fluentPort'], 'opts["fluentPort"]', null, null, false);
            $this->fluentPort = $opts['fluentPort'];
        }

        if (isset($opts['fluentTag'])) {
            $this->utilities()->validateString($opts['fluentTag'], 'opts["fluentTag"]', null, false);
            $this->fluentTag = $opts['fluentTag'];
        }
    }


    /**
     * @param \Rollbar\Payload\EncodedPayload $payload
     * @param string $accessToken
     * @return Response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) Unused parameter is
     * intended here to comply with SenderInterface
     */
    public function send(EncodedPayload $payload, string $accessToken): Response
    {
        if (empty($this->fluentLogger)) {
            $this->loadFluentLogger();
        }

        $scrubbedPayload = $payload->data();
        
        $success = $this->fluentLogger->post($this->fluentTag, $scrubbedPayload);
        $status = $success ? 200 : 400;
        $info = $success ? 'OK' : 'Bad Request';
        $uuid = $scrubbedPayload['data']['uuid'];

        return new Response($status, $info, $uuid);
    }

    public function sendBatch(array $batch, string $accessToken, &$responses = array ()): void
    {
        $responses = array();
        foreach ($batch as $payload) {
            $responses[] = $this->send($payload, $accessToken);
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
     * Returns true if the access token is required by the sender to send the payload. The Fluentd service can provide
     * its own access token.
     *
     * @return bool
     * @since 4.0.0
     */
    public function requireAccessToken(): bool
    {
        return false;
    }

    /**
     * Loads the fluent logger
     */
    protected function loadFluentLogger()
    {
        $this->fluentLogger = new FluentLogger($this->fluentHost, $this->fluentPort);
    }
}
