<?php namespace Rollbar;

use Rollbar\Rollbar;
use Rollbar\Payload\Level;
use Monolog\Logger;
use Rollbar\Monolog\Handler\RollbarHandler;

// used in testBasicUsage()
function do_something()
{
    throw new \Exception();
}

class ReadmeTest extends BaseRollbarTest
{
    public function testQuickStart(): void
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
        $response = Rollbar::report(LevelFactory::fromName(Level::INFO), 'testing wasSuccessful()');
        if (!$response->wasSuccessful()) {
            throw new \Exception('logging with Rollbar failed');
        }

        // raises an E_NOTICE which will *not* be reported by the error handler
        // $foo = $bar;

        // will be reported by the exception handler
        $this->expectException(\Exception::class);
        throw new \Exception('testing exception handler');
    }

    public function testSetup1(): void
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

    public function testSetup2(): void
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

    public function testBasicUsage(): void
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
            $result1 = Rollbar::report(Level::ERROR, $e);
            // or
            $result2 = Rollbar::report(Level::ERROR, $e, array("my" => "extra", "data" => 42));
        }
        
        $this->assertEquals(200, $result1->getStatus());
        $this->assertEquals(200, $result2->getStatus());
    }

    public function testBasicUsage2(): void
    {
        Rollbar::init(
            array(
                'access_token' => $this->getTestAccessToken(),
                'environment' => 'test'
            )
        );
        
        $result1 = Rollbar::report(Level::WARNING, 'could not connect to mysql server');
        $result2 = Rollbar::report(
            Level::INFO,
            'Here is a message with some additional data',
            array('x' => 10, 'code' => 'blue')
        );
        
        $this->assertEquals(200, $result1->getStatus());
        $this->assertEquals(200, $result2->getStatus());
    }
}
