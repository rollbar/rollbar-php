<?php declare(strict_types=1);

namespace Rollbar\Telemetry;

/**
 * The type of the telemetry event.
 *
 * This should be replaced by an enum when we only support PHP >= 8.1.
 *
 * @since 4.1.0
 */
class DataType
{
    const LOG = 'log';

    const NETWORK = 'network';

    /**
     * This is intended for use with browsers, and is only included here for API completeness. Generally, this should
     * not be used in a PHP context.
     */
    const DOM = 'dom';

    const NAVIGATION = 'navigation';

    const ERROR = 'error';

    const MANUAL = 'manual';
}
