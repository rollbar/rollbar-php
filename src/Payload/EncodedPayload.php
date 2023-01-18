<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Exception;
use Rollbar\Truncation\Truncation;

/**
 * This class handles the final stage of data JSON serialization just prior to sending to the Rollbar service.
 */
class EncodedPayload
{
    /**
     * The encoded data. Null if no data has been encoded yet.
     *
     * @var string|null
     */
    protected ?string $encoded = null;

    /**
     * The cached length of the encoded data.
     *
     * @var int
     */
    protected int $size = 0;

    /**
     * @param array $data The data to be encoded.
     */
    public function __construct(protected array $data)
    {
    }

    /**
     * Returns the data before it is encoded.
     *
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }

    /**
     * Returns the cached size of the encoded data.
     *
     * @return int
     */
    public function size(): int
    {
        return $this->size;
    }

    /**
     * Reduces the cached length of the encoded data by the $amount specified.
     *
     * Note: this does not reduce size of the data, only the cached size. To reduce the size of the payload data the
     * payload must be truncated. See {@see Truncation} for more details.
     *
     * @param int $amount The amount to decrease the cached data length.
     *
     * @return void
     */
    public function decreaseSize(int $amount): void
    {
        $this->size -= $amount;
    }

    /**
     * Updates the payload data and JSON serializes it. The serialized data string is cached and can be access via the
     * {@see encoded()} method.
     *
     * @param array|null $data If an array is given it will overwrite any existing payload data prior to serialization.
     *                         If null (the default value) is given the existing payload data will be serialized.
     *
     * @return void
     * @throws Exception If JSON serialization fails.
     */
    public function encode(?array $data = null): void
    {
        if ($data !== null) {
            $this->data = $data;
        }

        $this->encoded = json_encode(
            $this->data,
            defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0
        );

        if ($this->encoded === false) {
            throw new Exception("Payload data could not be encoded to JSON format.");
        }

        $this->size = strlen($this->encoded);
    }

    /**
     * Returns the encoded JSON.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->encoded();
    }

    /**
     * Returns the encoded JSON or null if the data to encode was null.
     *
     * @return string|null
     */
    public function encoded(): ?string
    {
        return $this->encoded;
    }
}
