<?php namespace Rollbar\Payload;

use Rollbar\Payload\Body;

class Payload {
    private $body;
    private $accessToken;

    public function __construct(Body $body, string $accessToken = null) {
        $this->body = $body;
        $this->accessToken = $accessToken;
    }
}
