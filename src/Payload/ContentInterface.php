<?php namespace Rollbar\Payload;

interface ContentInterface extends \Serializable
{
    public function getKey();
}
