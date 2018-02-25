<?php namespace Rollbar;

use Rollbar\Rollbar;
use Rollbar\Payload\Level;

// used in testBasicUsage()
function do_something()
{
    throw new \Exception();
}

class ReadmeTest extends BaseRollbarTest
{

    /**
     * @expectedException \Exception
     */
    public function testQuickStart()
    {
        // installs global error and exception handlers
        Rollbar::init(
            array(
                'access_token' => $this->getTestAccessToken(),
                'environment' => 'production'
            )
        );

        try {
            throw new \Exception('test exception');
        } catch (\Exception $e) {
            Rollbar::log(Level::ERROR, $e);
        }

        // Message at level 'info'
        Rollbar::log(Level::INFO, 'testing info level');
       
        // With extra data (3rd arg) and custom payload options (4th arg)
        Rollbar::log(
            Level::INFO,
            'testing extra data',
            array("some_key" => "some value") // key-value additional data
        );

        // If you want to check if logging with Rollbar was successful
        $response = Rollbar::log(Level::INFO, 'testing wasSuccessful()');
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
            'access_token' => $this->getTestAccessToken(),
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
            'access_token' => $this->getTestAccessToken(),
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
        Rollbar::init(
            array(
                'access_token' => $this->getTestAccessToken(),
                'environment' => 'test'
            )
        );
        
        try {
            do_something();
        } catch (\Exception $e) {
            $result1 = Rollbar::log(Level::ERROR, $e);
            // or
            $result2 = Rollbar::log(Level::ERROR, $e, array("my" => "extra", "data" => 42));
        }
    }

    public function testBasicUsage2()
    {
        Rollbar::init(
            array(
                'access_token' => $this->getTestAccessToken(),
                'environment' => 'test'
            )
        );
        
        $result1 = Rollbar::log(Level::WARNING, 'could not connect to mysql server');
        $result2 = Rollbar::log(
            Level::INFO,
            'Here is a message with some additional data',
            array('x' => 10, 'code' => 'blue')
        );
    }

    public function testMonolog()
    {
        $config = array('access_token' => $this->getTestAccessToken(), 'environment' => 'testing');

        // installs global error and exception handlers
        Rollbar::init($config);

        $log = new \Monolog\Logger('test');
        $log->pushHandler(new \Monolog\Handler\PsrHandler(Rollbar::logger()));

        try {
            throw new \Exception('exception for monolog');
        } catch (\Exception $e) {
            $log->error($e);
        }
    }
}
