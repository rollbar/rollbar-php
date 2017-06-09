<?php
namespace Rollbar;


abstract class BaseUnitTestCase extends \PHPUnit_Framework_TestCase
{

    protected function tearDown()
    {
        // make sure each test starts with a "fresh" instance
        Rollbar::reset();
        parent::tearDown();
    }
}
