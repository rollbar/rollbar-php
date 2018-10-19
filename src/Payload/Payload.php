<?php namespace Rollbar\Payload;

use Rollbar\DataBuilder;
use Rollbar\Config;

class Payload implements \Serializable
{
    private $data;
    private $accessToken;
    private $utilities;

    public function __construct(Data $data, $accessToken)
    {
        $this->utilities = new \Rollbar\Utilities();
        $this->setData($data);
        $this->setAccessToken($accessToken);
    }

    /**
     * @return Data
     */
    public function getData()
    {
        return $this->data;
    }

    public function setData(Data $data)
    {
        $this->data = $data;
        return $this;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function serialize($maxDepth = -1)
    {
        $objectHashes = array();
        $result = array(
            "data" => $this->data,
            "access_token" => $this->accessToken,
        );

        return $this->utilities->serializeForRollbar($result, null, $objectHashes, $maxDepth);
    }
    
    public function unserialize($serialized)
    {
        throw new \Exception('Not implemented yet.');
    }
}
