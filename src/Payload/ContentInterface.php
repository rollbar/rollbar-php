<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;

interface ContentInterface extends SerializerInterface
{
    public function getKey(): string;
}
