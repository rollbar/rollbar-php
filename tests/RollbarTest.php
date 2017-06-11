<?php namespace Rollbar;

if (!defined('ROLLBAR_TEST_TOKEN')) {
    define('ROLLBAR_TEST_TOKEN', 'ad865e76e7fb496fab096ac07b1dbabb');
}

use Rollbar\Rollbar;
use Rollbar\Payload\Level;

class RollbarTest extends \PHPUnit_Framework_TestCase
{

    private static $simpleConfig = array(
        'access_token' => ROLLBAR_TEST_TOKEN,
        'environment' => 'test'
    );
    
    public function setUp()
    {
        Rollbar::init(self::$simpleConfig);
    }
    
    public function testLogException()
    {
        try {
            throw new \Exception('test exception');
        } catch (\Exception $e) {
            Rollbar::log(Level::error(), $e);
        }
        
        $this->assertTrue(true);
    }
    
    public function testLogMessage()
    {
        Rollbar::log(Level::info(), 'testing info level');
        $this->assertTrue(true);
    }
    
    public function testLogExtraData()
    {
        Rollbar::log(
            Level::info(),
            'testing extra data',
            array("some_key" => "some value") // key-value additional data
        );
        
        $this->assertTrue(true);
    }

    /**
     * Below are backwards compatibility tests with v0.18.2
     */
    public function testBackwardsSimpleMessageVer()
    {
        Rollbar::init(self::$simpleConfig);

        $uuid = Rollbar::report_message("Hello world");
        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $uuid);
    }
    
    public function testBackwardsSimpleError()
    {
        Rollbar::init(self::$simpleConfig);
        
        $result = Rollbar::report_php_error(E_ERROR, "Runtime error", "the_file.php", 1);
        // always returns false.
        $this->assertFalse($result);
    }
    
    public function testBackwardsSimpleException()
    {
        Rollbar::init(self::$simpleConfig);
        
        $uuid = null;
        try {
            throw new \Exception("test exception");
        } catch (\Exception $e) {
            $uuid = Rollbar::report_exception($e);
        }

        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $uuid);
    }

    public function testBackwardsFlush()
    {
        Rollbar::flush();
        $this->assertTrue(true);
    }
}
