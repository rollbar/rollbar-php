<?php namespace Rollbar;

interface ResponseHandlerInterface
{
    public function handleResponse($payload, $response);
}
