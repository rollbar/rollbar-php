<?php declare(strict_types=1);

namespace Rollbar;

/**
 * The base logic required by all internal JSON serializable objects.
 */
interface SerializerInterface
{
    /**
     * Returns the JSON serializable representation of the object.
     *
     * @return array|string|null The string or array representation of the object or null
     */
    public function serialize();
}
