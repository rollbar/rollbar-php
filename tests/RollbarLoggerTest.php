<?php namespace Rollbar;

use Rollbar\RollbarLogger;
use Psr\Log\LogLevel;

class RollbarLoggerTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        parent::__construct();
        $_SESSION = array();
    }

    public function testConfigure()
    {
        $l = new RollbarLogger(array(
            "accessToken" => "accessaccesstokentokentokentoken",
            "environment" => "testing-php",
            "senderOptions" => array(
                "endpoint" => "http://dev:8090/api/1/item/"
            )
        ));
        $l->configure(array("extraData" => 15));
        $extended = $l->scope(array())->extend(array());
        $this->assertEquals(15, $extended['extraData']);
    }

    public function testLog()
    {
        $l = new RollbarLogger(array(
            "accessToken" => "e7bdee4192c44eb092b4dbfb822bc838",
            "environment" => "testing-php",
            "senderOptions" => array(
                "endpoint" => "http://dev:8090/api/1/item/"
            )
        ));
        $response = $l->log(LogLevel::WARNING, "Testing PHP Notifier", array());
        $this->assertEquals(200, $response->getStatus());
    }
}
