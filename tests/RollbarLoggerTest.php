<?php namespace Rollbar;

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
            "access_token" => "accessaccesstokentokentokentoken",
            "environment" => "testing-php"
        ));
        $l->configure(array("extraData" => 15));
        $extended = $l->scope(array())->extend(array());
        $this->assertEquals(15, $extended['extraData']);
    }

    public function testLog()
    {
        $l = new RollbarLogger(array(
            "access_token" => "ad865e76e7fb496fab096ac07b1dbabb",
            "environment" => "testing-php"
        ));
        $response = $l->log(LogLevel::WARNING, "Testing PHP Notifier", array());
        $this->assertEquals(200, $response->getStatus());
    }
}
