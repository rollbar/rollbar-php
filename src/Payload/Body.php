<?php declare(strict_types=1);

namespace Rollbar\Payload;

use Rollbar\SerializerInterface;
use Rollbar\UtilitiesTrait;

class Body implements SerializerInterface
{
    use UtilitiesTrait;

    /**
     * Creates a new instance of the Body class.
     *
     * @param ContentInterface      $value     The value to assign to the content property.
     * @param array                 $extra     An array to assign to the extra property. Default value is an empty
     *                                         array.
     * @param TelemetryEvent[]|null $telemetry An optional array of telemetry events. Default value is null.
     * @return void
     *
     * @since 4.1.0 The $telemetry property was added.
     */
    public function __construct(
        private ContentInterface $value,
        private array $extra = [],
        private ?array $telemetry = null
    ) {
    }

    /**
     * Returns the main content of the payload body.
     *
     * @return ContentInterface
     */
    public function getValue(): ContentInterface
    {
        return $this->value;
    }

    /**
     * Sets the main content of the payload body.
     *
     * @param ContentInterface $value The value to assign to the content of the payload body.
     *
     * @return self
     */
    public function setValue(ContentInterface $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Sets the array of extra data.
     *
     * @param array $extra The array of extra data.
     *
     * @return self
     */
    public function setExtra(array $extra): self
    {
        $this->extra = $extra;
        return $this;
    }

    /**
     * Returns the array of extra data.
     *
     * @return array
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * Returns the array of telemetry events or null if there were none.
     *
     * @return TelemetryEvent[]|null
     *
     * @since 4.1.0
     */
    public function getTelemetry(): ?array
    {
        if (empty($this->telemetry)) {
            return null;
        }
        return $this->telemetry;
    }

    /**
     * Sets the list of telemetry events for this payload body.
     *
     * @param array|null $telemetry The list of telemetry events or null if there were none.
     *
     * @return void
     *
     * @since 4.1.0
     */
    public function setTelemetry(?array $telemetry): void
    {
        $this->telemetry = $telemetry;
    }

    /**
     * Returns the JSON serializable representation of the payload body.
     *
     * @return array
     *
     * @since 4.1.0 Includes the 'telemetry' key, if it is not empty.
     */
    public function serialize()
    {
        $result = array();
        $result[$this->value->getKey()] = $this->value;

        if (!empty($this->extra)) {
            $result['extra'] = $this->extra;
        }

        if (!empty($this->telemetry)) {
            $result['telemetry'] = $this->telemetry;
        }

        return $this->utilities()->serializeForRollbarInternal($result, array('extra'));
    }
}
