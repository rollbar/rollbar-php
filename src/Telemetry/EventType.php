<?php declare(strict_types=1);

namespace Rollbar\Telemetry;

/**
 * The type of the telemetry event.
 *
 * @since 4.1.0
 */
enum EventType: string
{
    case Log = 'log';

    case Network = 'network';

    /**
     * This is intended for use with browsers, and is only included here for API completeness. Generally, this should
     * not be used in a PHP context.
     */
    case DOM = 'dom';

    case Navigation = 'navigation';

    case Error = 'error';

    case Manual = 'manual';
}
