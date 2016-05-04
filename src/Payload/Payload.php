<?php namespace Rollbar\Payload;

use Rollbar\Payload\Data;
use Rollbar\Utilities;

class Payload implements \JsonSerializable
{
    private $data;
    private $accessToken;

    public function __construct(Data $data, $accessToken = null)
    {
        $this->setData($data);
        $this->setAccessToken($accessToken);
    }

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
        if (!is_null($accessToken)) {
            Utilities::validateString($accessToken, "accessToken", 32);
        }
        $this->accessToken = $accessToken;
        return $this;
    }

    public function jsonSerialize()
    {
        return Utilities::serializeForRollbar(get_object_vars($this));
    }
}
