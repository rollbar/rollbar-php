<?php namespace Rollbar;

use Mockery as m;
use Rollbar\Payload\Notifier;

class NotifierTest extends BaseRollbarTest
{
    public function testName(): void
    {
        $name = "rollbar-php";
        $notifier = new Notifier($name, "0.1");
        $this->assertEquals($name, $notifier->getName());

        $name2 = "RollbarPHP";
        $this->assertEquals($name2, $notifier->setName($name2)->getName());
    }

    public function testVersion(): void
    {
        $version = Notifier::VERSION;
        $notifier = new Notifier("PHP-Rollbar", $version);
        $this->assertEquals($version, $notifier->getVersion());

        $version2 = "0.9";
        $this->assertEquals($version2, $notifier->setVersion($version2)->getVersion());
    }

    public function testDefaultNotifierIsRepresentableAsJson(): void
    {
        $notifier = Notifier::defaultNotifier()->serialize();
        $encoding = json_encode($notifier, flags: JSON_THROW_ON_ERROR|JSON_FORCE_OBJECT);
        $decoding = json_decode($encoding, flags: JSON_THROW_ON_ERROR);
        $this->assertObjectHasAttribute('name', $decoding);
        $this->assertObjectHasAttribute('version', $decoding);
    }

    public function testDefaultNotifierVersionIsSemVerCompliant(): void
    {
        // https://semver.org/#is-there-a-suggested-regular-expression-regex-to-check-a-semver-string
        $semVerRegex = '/
            (0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)
            (?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)
            (?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?
            (?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?
        /x';
        $this->assertMatchesRegularExpression(
            $semVerRegex,
            Notifier::defaultNotifier()->getVersion()
        );
    }
}
