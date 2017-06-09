<?php namespace Rollbar;

use Rollbar\Payload\Server;

class ServerTest extends BaseUnitTestCase
{
    public function testHost()
    {
        $val = "TEST";
        $server = new Server();
        $server->setHost($val);
        $this->assertEquals($val, $server->getHost());

        $val2 = "TEST2";
        $this->assertEquals($val2, $server->setHost($val2)->getHost());
    }

    public function testRoot()
    {
        $val = "TEST";
        $server = new Server();
        $server->setRoot($val);
        $this->assertEquals($val, $server->getRoot());

        $val2 = "TEST2";
        $this->assertEquals($val2, $server->setRoot($val2)->getRoot());
    }

    public function testBranch()
    {
        $val = "TEST";
        $server = new Server();
        $server->setBranch($val);
        $this->assertEquals($val, $server->getBranch());

        $val2 = "TEST2";
        $this->assertEquals($val2, $server->setBranch($val2)->getBranch());
    }

    public function testCodeVersion()
    {
        $val = "TEST";
        $server = new Server();
        $server->setCodeVersion($val);
        $this->assertEquals($val, $server->getCodeVersion());

        $val2 = "TEST2";
        $this->assertEquals($val2, $server->setCodeVersion($val2)->getCodeVersion());
    }

    public function testExtra()
    {
        $server = new Server();
        $server->test = "testing";
        $this->assertEquals("testing", $server->test);
    }

    public function testEncode()
    {
        $server = new Server();
        $server->setHost("server2-ec-us")
            ->setRoot("/home/app/testingRollbar")
            ->setBranch("master")
            ->setCodeVersion("#dca015");
        $server->test = array(1, 2, "3", array());
        $expected = '{' .
            '"host":"server2-ec-us",' .
            '"root":"\\/home\\/app\\/testingRollbar",' .
            '"branch":"master",' .
            '"code_version":"#dca015",' .
            '"test":' .
                '[' .
                    '1,' .
                    '2,' .
                    '"3",' .
                    '[]' .
                ']' .
            '}';
        $this->assertEquals($expected, json_encode($server->jsonSerialize()));
    }
}
