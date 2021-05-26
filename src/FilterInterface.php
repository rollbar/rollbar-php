<?php declare(strict_types=1);

namespace Rollbar;

interface FilterInterface
{
    public function shouldSend($payload, $accessToken);
}
