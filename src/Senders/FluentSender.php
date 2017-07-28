<?php

namespace Rollbar\Senders;

use Fluent\Logger\FluentLogger;
use Rollbar\Response;

class FluentSender implements SenderInterface
{
    /**
     * @var Utilities
     */
    private $utilities;

    /**
     * @var FluentLogger FluentLogger instance
     */
    private $fluentLogger = null;

    /**
     * @var string IP of the fluentd host
     */
    private $fluentHost = FluentLogger::DEFAULT_ADDRESS;

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
        $this->utilities = new \Rollbar\Utilities();
        if (isset($opts['fluentHost'])) {
            $this->utilities->validateString($opts['fluentHost'], 'opts["fluentHost"]', null, false);
            $this->fluentHost = $opts['fluentHost'];
        }

        if (isset($opts['fluentPort'])) {
            $this->utilities->validateInteger($opts['fluentPort'], 'opts["fluentPort"]', null, null, false);
            $this->fluentPort = $opts['fluentPort'];
        }

        if (isset($opts['fluentTag'])) {
            $this->utilities->validateString($opts['fluentTag'], 'opts["fluentTag"]', null, false);
            $this->fluentTag = $opts['fluentTag'];
        }
    }


    /**
     * @param $scrubbedPayload
     * @param $accessToken
     * @return Response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) Unsued parameter is
     * intended here to comply to SenderInterface
     */
    public function send($scrubbedPayload, $accessToken)
    {
        if (empty($this->fluentLogger)) {
            $this->loadFluentLogger();
        }

        $success = $this->fluentLogger->post($this->fluentTag, $scrubbedPayload);
        $status = $success ? 200 : 400;
        $info = $success ? 'OK' : 'Bad Request';
        $uuid = $scrubbedPayload['data']['uuid'];

        return new Response($status, $info, $uuid);
    }

    public function sendBatch($batch, $accessToken)
    {
        $responses = array();
        foreach ($batch as $payload) {
            $responses[] = $this->send($payload, $accessToken);
        }
        return $responses;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function wait($accessToken, $max)
    {
        return;
    }

    /**
     * Loads the fluent logger
     */
    protected function loadFluentLogger()
    {
        $this->fluentLogger = new FluentLogger($this->fluentHost, $this->fluentPort);
    }
}
