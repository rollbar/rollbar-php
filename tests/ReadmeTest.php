<?php namespace Rollbar;

if (!defined('ROLLBAR_TEST_TOKEN')) {
    define('ROLLBAR_TEST_TOKEN', 'ad865e76e7fb496fab096ac07b1dbabb');
}


use Rollbar\Rollbar;
use Rollbar\Payload\Level;
use Monolog\Logger;

// used in testBasicUsage()
function do_something()
{
    throw new \Exception();
}

class ReadmeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \Exception
     */
    public function testQuickStart()
    {
        // installs global error and exception handlers
        Rollbar::init(
            array(
                'access_token' => ROLLBAR_TEST_TOKEN,
                'environment' => 'production'
            )
        );

        try {
            throw new \Exception('test exception');
        } catch (\Exception $e) {
            Rollbar::log(Level::error(), $e);
        }

        // Message at level 'info'
        Rollbar::log(Level::info(), 'testing info level');

        // With extra data (3rd arg) and custom payload options (4th arg)
        Rollbar::log(
            Level::info(),
            'testing extra data',
            array("some_key" => "some value") // key-value additional data
        );

        // If you want to check if logging with Rollbar was successful
        $response = Rollbar::log(Level::info(), 'testing wasSuccessful()');
        if (!$response->wasSuccessful()) {
            throw new \Exception('logging with Rollbar failed');
        }

        // raises an E_NOTICE which will *not* be reported by the error handler
        // $foo = $bar;

        // will be reported by the exception handler
        throw new \Exception('testing exception handler');
    }

    public function testSetup1()
    {
        $config = array(
            // required
            'access_token' => ROLLBAR_TEST_TOKEN,
            // optional - environment name. any string will do.
            'environment' => 'production',
            // optional - path to directory your code is in. used for linking stack traces.
            'root' => '/Users/brian/www/myapp'
        );
        Rollbar::init($config);

        $this->assertTrue(true);
    }

    public function testSetup2()
    {
        $config = array(
            // required
            'access_token' => ROLLBAR_TEST_TOKEN,
            // optional - environment name. any string will do.
            'environment' => 'production',
            // optional - path to directory your code is in. used for linking stack traces.
            'root' => '/Users/brian/www/myapp'
        );

        $set_exception_handler = false;
        $set_error_handler = false;
        Rollbar::init($config, $set_exception_handler, $set_error_handler);

        $this->assertTrue(true);
    }

    public function testBasicUsage()
    {
        try {
            do_something();
        } catch (\Exception $e) {
            Rollbar::log(Level::error(), $e);
            // or
            Rollbar::log(Level::error(), $e, array("my" => "extra", "data" => 42));
        }
    }

    public function testBasicUsage2()
    {
        Rollbar::log(Level::warning(), 'could not connect to mysql server');
        Rollbar::log(
            Level::info(),
            'Here is a message with some additional data',
            array('x' => 10, 'code' => 'blue')
        );
    }

    public function testMonolog()
    {
        $config = array('access_token' => ROLLBAR_TEST_TOKEN, 'environment' => 'testing');

        // installs global error and exception handlers
        Rollbar::init($config);

        $log = new Logger('test');
        $log->pushHandler(new \Monolog\Handler\PsrHandler(Rollbar::logger()));

        try {
            throw new \Exception('exception for monolog');
        } catch (\Exception $e) {
            $log->error($e);
        }
    }
}
