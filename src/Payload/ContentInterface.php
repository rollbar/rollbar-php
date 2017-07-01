<?php namespace Rollbar\Payload;

interface ContentInterface extends \JsonSerializable
{
    public function getKey();
}
