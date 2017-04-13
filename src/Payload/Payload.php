<?php namespace Rollbar\Payload;

use Rollbar\Utilities;
use Rollbar\DataBuilder;
use Rollbar\Config;

class Payload implements \JsonSerializable
{
    private $data;
    private $accessToken;
    private $config;

    public function __construct(Data $data, Config $config)
    {
        $this->setData($data);
        $this->setAccessToken($config->getAccessToken());
        $this->config = $config;
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
        Utilities::validateString($accessToken, "accessToken", 32, false);
        $this->accessToken = $accessToken;
        return $this;
    }

    public function jsonSerialize()
    {
        $serialized = Utilities::serializeForRollbar(array(
            'data' => $this->data,
            'accessToken' => $this->accessToken
        ));
        
        $dataBuilder = $this->config->getDataBuilder();
        $serialized['data'] = $dataBuilder->scrub($serialized['data']);
        
        return $serialized;
    }
}
