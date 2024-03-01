<?php

namespace Rollbar\TestHelpers;

use Closure;
use Rollbar\Payload\TelemetryEvent;
use Rollbar\Telemetry\TelemetryFilterInterface;
use Stringable;

class TestTelemetryFilter implements TelemetryFilterInterface
{
    public array $config;
    public ?Closure $includeFunction = null;

    public ?Closure $includeRollbarItemFunction = null;

    public bool $filterOnRead = true;

    /**
     * @param array $config The telemetry config.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function include(TelemetryEvent $event, int $queueSize): bool
    {
        return $this->includeFunction?->call($this, $event, $queueSize) ?? false;
    }

    /**
     * @inheritDoc
     */
    public function includeRollbarItem(
        string $level,
        Stringable|string $message,
        array $context = [],
        bool $ignored = false,
    ): bool {
        return $this->includeRollbarItemFunction?->call($this, $level, $message, $context, $ignored) ?? false;
    }

    /**
     * @inheritDoc
     */
    public function filterOnRead(): bool
    {
        return $this->filterOnRead;
    }
}
