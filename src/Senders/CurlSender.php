<?php namespace Rollbar\Senders;

/**
 * A lot of this class is ripped off from Segment:
 * https://github.com/segmentio/analytics-php/blob/master/lib/Segment/Consumer/Socket.php
 */

use Rollbar\Response;
use Rollbar\Payload\Payload;
use Rollbar\Utilities;

class CurlSender implements SenderInterface
{
    private $endpoint = 'https://api.rollbar.com/api/1/item/';
    private $timeout = 3;
    private $proxy = null;
    private $verifyPeer = true;

    public function __construct($opts)
    {
        if (isset($_ENV['ROLLBAR_ENDPOINT']) && !isset($opts['endpoint'])) {
            $opts['endpoint'] = $_ENV['ROLLBAR_ENDPOINT'];
        }
        if (array_key_exists('endpoint', $opts)) {
            Utilities::validateString($opts['endpoint'], 'opts["endpoint"]', null, false);
            $this->endpoint = $opts['endpoint'];
        }
        if (array_key_exists('timeout', $opts)) {
            Utilities::validateInteger($opts['timeout'], 'opts["timeout"]', 0, null, false);
            $this->timeout = $opts['timeout'];
        }
        if (array_key_exists('proxy', $opts)) {
            $this->proxy = $opts['proxy'];
        }

        if (array_key_exists('verifyPeer', $opts)) {
            Utilities::validateBoolean($opts['verifyPeer'], 'opts["verifyPeer"]', false);
            $this->verifyPeer = $opts['verifyPeer'];
        }
    }

    public function send(Payload $payload, $accessToken)
    {

        $ch = curl_init();

        $this->setCurlOptions($ch, $payload, $accessToken);
        $result = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $uuid = $payload->getData()->getUuid();
        return new Response($statusCode, json_decode($result, true), $uuid);
    }

    public function setCurlOptions($ch, Payload $payload, $accessToken)
    {
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        $encoded = json_encode($payload->jsonSerialize());
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifyPeer);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Rollbar-Access-Token: ' . $accessToken));
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        if ($this->proxy) {
            $proxy = is_array($this->proxy) ? $this->proxy : array('address' => $this->proxy);
            if (isset($proxy['address'])) {
                curl_setopt($ch, CURLOPT_PROXY, $proxy['address']);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            }
            if (isset($proxy['username']) && isset($proxy['password'])) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['username'] . ':' . $proxy['password']);
            }
        }
    }
}
