<?php namespace Rollbar;

interface FilterInterface
{
    public function shouldSend($payload, $accessToken);
}
