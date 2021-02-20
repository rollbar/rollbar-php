<?php declare(strict_types=1);

namespace Rollbar\Payload;

interface ContentInterface extends \Serializable
{
    public function getKey();
}
