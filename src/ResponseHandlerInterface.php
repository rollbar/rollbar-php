<?php declare(strict_types=1);

namespace Rollbar;

interface ResponseHandlerInterface
{
    public function handleResponse($payload, $response);
}
