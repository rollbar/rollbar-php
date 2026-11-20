<?php declare(strict_types=1);

namespace Rollbar;

use Exception;

/**
 * Not intended for throwing, just for sending a log message to Rollbar with a stack trace.
 *
 * @since 4.3.0
 */
class LogInfo extends Exception
{
}
