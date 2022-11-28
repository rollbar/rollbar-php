<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;

/**
 * The logic required for the payload body content.
 */
interface ContentInterface extends SerializerInterface
{
    /**
     * Returns the key associated with the body content type used in JSON serialization.
     *
     * @return string
     */
    public function getKey(): string;
}
