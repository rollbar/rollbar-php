<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\CrashReport;

class CrashReportTest extends \PHPUnit_Framework_TestCase
{
    public function testCrashReportRaw()
    {
        $raw = "RAW ERROR, TYPICALLY FROM iDevices";
        $crashReport = new CrashReport($raw);
        $this->assertEquals($raw, $crashReport->getRaw());

        $raw2 = "MEMORY DUMP FROM LINUX, NOT SUPPORTED YET";
        $this->assertEquals($raw2, $crashReport->setRaw($raw2)->getRaw());
    }

    public function testEncode()
    {
        $crashReport = new CrashReport("TEST");
        $encoded = json_encode($crashReport);
        $this->assertEquals('{"raw":"TEST"}', $encoded);
    }
}
