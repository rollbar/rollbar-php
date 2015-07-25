<?php

if (!defined('ROLLBAR_TEST_TOKEN')) {
    define('ROLLBAR_TEST_TOKEN', 'ad865e76e7fb496fab096ac07b1dbabb');
}

class RollbarTest extends PHPUnit_Framework_TestCase {

    private static $simpleConfig = array(
        'access_token' => ROLLBAR_TEST_TOKEN,
        'environment' => 'test'
    );

    protected function setUp() {
        Rollbar::$instance = null;
    }

    public function testInit() {
        Rollbar::init(self::$simpleConfig);

        $this->assertEquals(ROLLBAR_TEST_TOKEN, Rollbar::$instance->access_token);
        $this->assertEquals('test', Rollbar::$instance->environment);
    }

    public function testSimpleMessage() {
        Rollbar::init(self::$simpleConfig);

        $uuid = Rollbar::report_message("Hello world");
        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $uuid);
    }
    
    public function testMessageBeforeInit() {
        $uuid = Rollbar::report_message("Hello world");
        $this->assertNull($uuid);
    }
    
    public function testSimpleError() {
        Rollbar::init(self::$simpleConfig);
        
        $result = Rollbar::report_php_error(E_ERROR, "Runtime error", "the_file.php", 1);
        // always returns false.
        $this->assertFalse($result);
    }

    public function testErrorBeforeInit() {
        $uuid = Rollbar::report_php_error(E_ERROR, "Runtime error", "the_file.php", 1);
        $this->assertFalse($uuid);
    }
    
    public function testSimpleException() {
        Rollbar::init(self::$simpleConfig);
        
        $uuid = null;
        try {
            throw new Exception("test exception");
        } catch (Exception $e) {
            $uuid = Rollbar::report_exception($e);
        }

        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $uuid);
    }
    
    public function testExceptionBeforeInit() {
        $uuid = null;
        try {
            throw new Exception("test exception");
        } catch (Exception $e) {
            $uuid = Rollbar::report_exception($e);
        }
        $this->assertNull($uuid);
    }

    public function testFlush() {
        Rollbar::init(self::$simpleConfig);
        $this->assertEquals(0, Rollbar::$instance->queueSize());
        
        Rollbar::report_message("Hello world");
        $this->assertEquals(1, Rollbar::$instance->queueSize());

        Rollbar::flush();
        $this->assertEquals(0, Rollbar::$instance->queueSize());
    }

    public function testScrub() {
        Rollbar::init(self::$simpleConfig);

        $method = new ReflectionMethod(get_class(Rollbar::$instance), 'scrub_request_params');
        $method->setAccessible(true);

        Rollbar::$instance->scrub_fields = array('secret', 'scrubme');

        $this->assertEquals(
            $method->invoke(
                Rollbar::$instance,
                array(
                    'some_item',
                    'apples' => array(
                        'green',
                        'red'
                    ),
                    'bananas' => array(
                        'yellow'
                    ),
                    'secret' => 'shh',
                    'a' => array(
                        'b' => array(
                            'secret' => 'deep',
                            'scrubme' => 'secrets'
                        )
                    )
                )
            ),
            array(
                'some_item',
                'apples' => array(
                    'green',
                    'red'
                ),
                'bananas' => array(
                    'yellow'
                ),
                'secret' => '***',
                'a' => array(
                    'b' => array(
                        'secret' => '****',
                        'scrubme' => '*******'
                    )
                )
            )
        );
    }

}

?>
