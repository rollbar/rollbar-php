<?php namespace Rollbar\Payload;

use Rollbar\Payload\Body;
use Rollbar\Utilities;

class Payload {
    private $body;
    private $accessToken;

    public function __construct(Body $body, $accessToken = null) {
        $this->body = $body;
        $this->setAccessToken($accessToken);
    }

    public function setAccessToken($accessToken) {
        if (!is_null($accessToken)) {
            Utilities::isString($accessToken, "accessToken", 32);
        }
        $this->accessToken = $accessToken;
    }
}
