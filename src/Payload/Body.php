<?php namespace Rollbar\Payload;

class Body extends \JsonSerializable {
    private $content;

    public function __construct(ContentInterface content) {
        $this->content = content;
    }

    public function jsonSerialize() {

    }
}
