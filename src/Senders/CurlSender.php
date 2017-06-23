<?php namespace Rollbar\Senders;

/**
 * A lot of this class is ripped off from Segment:
 * https://github.com/segmentio/analytics-php/blob/master/lib/Segment/Consumer/Socket.php
 */

use Rollbar\Response;
use Rollbar\Payload\Payload;

class CurlSender implements SenderInterface
{
    private $utilities;
    private $endpoint = 'https://api.rollbar.com/api/1/item/';
    private $timeout = 3;
    private $proxy = null;
    private $verifyPeer = true;

    public function __construct($opts)
    {
        $this->utilities = new \Rollbar\Utilities();
        if (isset($_ENV['ROLLBAR_ENDPOINT']) && !isset($opts['endpoint'])) {
            $opts['endpoint'] = $_ENV['ROLLBAR_ENDPOINT'];
        }
        if (array_key_exists('endpoint', $opts)) {
            $this->utilities->validateString($opts['endpoint'], 'opts["endpoint"]', null, false);
            $this->endpoint = $opts['endpoint'];
        }
        if (array_key_exists('timeout', $opts)) {
            $this->utilities->validateInteger($opts['timeout'], 'opts["timeout"]', 0, null, false);
            $this->timeout = $opts['timeout'];
        }
        if (array_key_exists('proxy', $opts)) {
            $this->proxy = $opts['proxy'];
        }

        if (array_key_exists('verifyPeer', $opts)) {
            $this->utilities->validateBoolean($opts['verifyPeer'], 'opts["verifyPeer"]', false);
            $this->verifyPeer = $opts['verifyPeer'];
        }
    }
    
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    public function send($scrubbedPayload, $accessToken)
    {

        $handle = curl_init();

        $this->setCurlOptions($handle, $scrubbedPayload, $accessToken);
        $result = curl_exec($handle);
        $statusCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        curl_close($handle);

        $uuid = $scrubbedPayload['data']['uuid'];
        return new Response($statusCode, json_decode($result, true), $uuid);
    }

    public function setCurlOptions($handle, $scrubbedPayload, $accessToken)
    {
        curl_setopt($handle, CURLOPT_URL, $this->endpoint);
        curl_setopt($handle, CURLOPT_POST, true);
        $encoded = json_encode($scrubbedPayload);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $encoded);
        curl_setopt($handle, CURLOPT_VERBOSE, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, $this->verifyPeer);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array('X-Rollbar-Access-Token: ' . $accessToken));
        curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        if ($this->proxy) {
            $proxy = is_array($this->proxy) ? $this->proxy : array('address' => $this->proxy);
            if (isset($proxy['address'])) {
                curl_setopt($handle, CURLOPT_PROXY, $proxy['address']);
                curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
            }
            if (isset($proxy['username']) && isset($proxy['password'])) {
                curl_setopt($handle, CURLOPT_PROXYUSERPWD, $proxy['username'] . ':' . $proxy['password']);
            }
        }
    }
}
