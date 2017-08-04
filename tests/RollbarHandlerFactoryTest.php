<?php

namespace Rollbar;

/**
 * Usage of static method RollbarHandlerFactory::create() is intended here.
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class RollbarHandlerFactoryTest extends BaseRollbarTest
{
    public function __construct()
    {
        self::$simpleConfig['access_token'] = $this->getTestAccessToken();
        self::$simpleConfig['environment'] = 'test';

        parent::__construct();
    }

    private static $simpleConfig = array();

    public function testInitWithConfig()
    {
        $handler = RollbarHandlerFactory::create(self::$simpleConfig);

        $this->assertInstanceOf('Monolog\Handler\PsrHandler', $handler);
    }
}
