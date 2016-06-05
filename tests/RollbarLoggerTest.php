<?php namespace Rollbar;

use Rollbar\RollbarLogger;
use Psr\Log\LogLevel;

class RollbarLoggerTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $_SESSION = array();
    }


    public function testConfigure()
    {
        $l = new RollbarLogger(array(
            "accessToken" => "accessaccesstokentokentokentoken",
            "environment" => "testing-php"
        ));
        $l->configure(array("extraData" => 15));
        $extended = $l->scope(array())->extend(array());
        $this->assertEquals(15, $extended['extraData']);
    }

    public function testLog()
    {
        $l = new RollbarLogger(array(
            "accessToken" => "ad865e76e7fb496fab096ac07b1dbabb",
            "environment" => "testing-php"
        ));
        $response = $l->log(LogLevel::WARNING, "Testing PHP Notifier", array());
        var_dump("\n\n" . (string) $response . "\n\n");
        $this->assertEquals(200, $response->getStatus());
    }
}
