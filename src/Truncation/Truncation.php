<?php declare(strict_types=1);

namespace Rollbar\Truncation;

use Exception;
use Rollbar\Payload\EncodedPayload;
use Rollbar\Config;

/**
 * The payload truncation manager.
 *
 * @since 1.2.0
 */
class Truncation
{
    /**
     * If a payload is smaller than this it will not be truncated.
     */
    const MAX_PAYLOAD_SIZE = 131072; // 128 * 1024

    /**
     * @var string[] The list of truncation strategies to apply in order.
     */
    protected static array $truncationStrategies = array(
        FramesStrategy::class,
        StringsStrategy::class,
    );

    /**
     * Creates the truncation manager class and attempts to register a custom truncation strategy from the configs if
     * it exists.
     *
     * @throws Exception If the strategy class does not implement {@see StrategyInterface}.
     */
    public function __construct(private Config $config)
    {
        if ($custom = $config->getCustomTruncation()) {
            $this->registerStrategy($custom);
        }
    }

    /**
     * Adds a new truncation strategy to the list of strategies used to truncate large payloads before they are sent
     * over the wire. A strategy registered with this method will be used before any existing ones are used. It does
     * not replace or remove existing strategies.
     *
     * @param string $type The fully qualified class name of a truncation strategy. The strategy must implement the
     *                     {@see StrategyInterface} interface.
     *
     * @return void
     * @throws Exception If the strategy class does not implement {@see StrategyInterface}.
     */
    public function registerStrategy(string $type): void
    {
        if (!class_exists($type)) {
            throw new Exception('Truncation strategy "' . $type . '" doesn\'t exist.');
        }
        if (!in_array(StrategyInterface::class, class_implements($type))) {
            throw new Exception(
                'Truncation strategy "' . $type . '" doesn\'t implement ' . StrategyInterface::class
            );
        }
        array_unshift(static::$truncationStrategies, $type);
    }

    /**
     * Applies truncation strategies in order to keep the payload size under
     * configured limit.
     *
     * @param EncodedPayload $payload The payload that may need to be truncated.
     *
     * @return EncodedPayload
     */
    public function truncate(EncodedPayload $payload): EncodedPayload
    {
        foreach (static::$truncationStrategies as $strategy) {
            $strategy = new $strategy($this);

            if (!$strategy->applies($payload)) {
                continue;
            }

            if (!$this->needsTruncating($payload)) {
                break;
            }

            $this->config->verboseLogger()->debug('Applying truncation strategy ' . get_class($strategy));

            $payload = $strategy->execute($payload);
        }

        return $payload;
    }

    /**
     * Check if the payload is too big to be sent
     *
     * @param EncodedPayload $payload The payload that may need to be truncated.
     *
     * @return boolean
     */
    public function needsTruncating(EncodedPayload $payload): bool
    {
        return $payload->size() > self::MAX_PAYLOAD_SIZE;
    }
}
