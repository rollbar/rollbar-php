<?php
namespace Rollbar;

use PHPUnit\Framework\TestCase;

abstract class BaseRollbarTest extends TestCase
{
    
    const DEFAULT_ACCESS_TOKEN = 'ad865e76e7fb496fab096ac07b1dbabb';
    
    public function tearDown()
    {
        Rollbar::destroy();
        parent::tearDown();
    }
    
    public function getTestAccessToken()
    {
        return isset($_ENV['ROLLBAR_TEST_TOKEN']) ?
            $_ENV['ROLLBAR_TEST_TOKEN'] :
            static::DEFAULT_ACCESS_TOKEN;
    }
}
