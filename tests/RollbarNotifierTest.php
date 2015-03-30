<?php

define('ROLLBAR_TEST_TOKEN', 'ad865e76e7fb496fab096ac07b1dbabb');

class RollbarNotifierTest extends PHPUnit_Framework_TestCase {
    
    private static $simpleConfig = array(
        'access_token' => ROLLBAR_TEST_TOKEN,
        'environment' => 'test'
    );

    public function testConstruct() {
        $notifier = new RollbarNotifier(self::$simpleConfig);

        $this->assertEquals('ad865e76e7fb496fab096ac07b1dbabb', $notifier->access_token);
        $this->assertEquals('test', $notifier->environment);
    }

    public function testSimpleMessage() {
        $notifier = new RollbarNotifier(self::$simpleConfig);

        $uuid = $notifier->report_message("Hello world");
        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $uuid);
    }

    public function testSimpleError() {
        $notifier = new RollbarNotifier(self::$simpleConfig);
        
        $uuid = $notifier->report_php_error(E_ERROR, "Runtime error", "the_file.php", 1);
        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $uuid);
    }

    public function testSimpleException() {
        $notifier = new RollbarNotifier(self::$simpleConfig);

        $uuid = null;
        try {
            throw new Exception("test exception");
        } catch (Exception $e) {
            $uuid = $notifier->report_exception($e);
        }

        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $uuid);
    }

    public function testFlush() {
        $notifier = new RollbarNotifier(self::$simpleConfig);
        $this->assertEquals(0, $notifier->queueSize());
        
        $notifier->report_message("Hello world");
        $this->assertEquals(1, $notifier->queueSize());
        
        $notifier->flush();
        $this->assertEquals(0, $notifier->queueSize());
    }

    public function testMessageWithStaticPerson() {
        $config = self::$simpleConfig;
        $config['person'] = array('id' => '123', 'username' => 'example', 'email' => 'example@example.com');
        $notifier = new RollbarNotifier($config);

        $uuid = $notifier->report_message('Hello world');

        // TODO assert that the payload actually contains the person
        
        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $uuid);
    }
    
    public function testMessageWithDynamicPerson() {
        $config = self::$simpleConfig;
        $config['person_fn'] = 'dummy_rollbar_person_fn';
        $notifier = new RollbarNotifier($config);

        $uuid = $notifier->report_message('Hello world');

        // TODO assert that the payload actually contains the person
        
        $this->assertStringMatchesFormat('%x-%x-%x-%x-%x', $uuid);
    }

}

function dummy_rollbar_person_fn() {
    return array('id' >= 456, 'username' => 'example', 'email' => 'example@example.com');
}

?>
