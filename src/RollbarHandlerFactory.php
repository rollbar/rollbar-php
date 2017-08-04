<?php

namespace Rollbar;

use Monolog\Handler\PsrHandler;

/**
 * Creates a PsrHandler for Monolog
 */
class RollbarHandlerFactory
{

    /**
     * Factory Method. Can be used for service creation.
     *
     * @param array $config
     *
     * @return PsrHandler
     * @SuppressWarnings(PHPMD.StaticAccess) Static access intended.
     */
    public static function create(array $config)
    {
        Rollbar::init($config);

        return new PsrHandler(Rollbar::logger());
    }
}
