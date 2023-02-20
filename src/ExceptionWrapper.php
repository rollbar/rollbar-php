<?php declare(strict_types=1);

namespace Rollbar;

use Stringable;
use Throwable;

/**
 * The exception wrapper class is used to annotate an exception with a caught/uncaught flag. This is used internally by
 * our handlers when passing exceptions to the {@see RollbarLogger::log()} method, since it must conform to the PSR
 * logger interface.
 */
class ExceptionWrapper implements Stringable
{
    /**
     * Instantiates the exception wrapper.
     *
     * @param Throwable $exception  The exception to wrap.
     * @param bool      $isUncaught Whether the exception was caught or not.
     */
    public function __construct(
        private Throwable $exception,
        public bool $isUncaught = false,
    ) {
    }

    /**
     * Returns the wrapped exception.
     *
     * @return Throwable
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }

    /**
     * This wrapper should be as transparent as possible, so we just pass through the exception
     * {@see Throwable::__toString method.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->exception->__toString();
    }
}
