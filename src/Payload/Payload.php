<?php namespace Rollbar\Payload;

use Rollbar\Utilities;

class Payload implements \JsonSerializable
{
    private $data;
    private $accessToken;

    public function __construct(Data $data, $accessToken)
    {
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
        Utilities::validateString($accessToken, "accessToken", 32, false);
        $this->accessToken = $accessToken;
        return $this;
    }

    public function jsonSerialize()
    {
        return Utilities::serializeForRollbar(get_object_vars($this));
    }
}
