<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Notifier;

class NotifierTest extends BaseRollbarTest
{
    public function testName()
    {
        $name = "rollbar-php";
        $notifier = new Notifier($name, "0.1");
        $this->assertEquals($name, $notifier->getName());

        $name2 = "RollbarPHP";
        $this->assertEquals($name2, $notifier->setName($name2)->getName());
    }

    public function testVersion()
    {
        $version = Notifier::VERSION;
        $notifier = new Notifier("PHP-Rollbar", $version);
        $this->assertEquals($version, $notifier->getVersion());

        $version2 = "0.9";
        $this->assertEquals($version2, $notifier->setVersion($version2)->getVersion());
    }

    public function testEncode()
    {
        $notifier = Notifier::defaultNotifier();
        $encoded = json_encode($notifier->jsonSerialize());
        $this->assertEquals('{"name":"rollbar-php","version":"1.4.2"}', $encoded);
    }
}
