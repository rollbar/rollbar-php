<?php

namespace Rollbar\Telemetry;

/**
 * The level of the telemetry event.
 *
 * @since 4.1.0
 */
enum EventLevel: string
{
    case Debug = 'debug';

    case Info = 'info';

    case Warning = 'warning';

    case Error = 'error';

    case Critical = 'critical';
}
