<?php

namespace Rollbar\TestHelpers {
    /**
     * Load only in isolated test processes: this file overrides Rollbar's random_bytes() calls.
     */
    final class RandomBytesStub
    {
        public static \Closure $callback;
    }
}

namespace Rollbar {
    function random_bytes(int $length): string
    {
        return (TestHelpers\RandomBytesStub::$callback)($length);
    }
}
