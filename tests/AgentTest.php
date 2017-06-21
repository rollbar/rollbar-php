<?php

namespace Rollbar\Senders; // in a different namespace, so we can monkey-patch microtime.

use Rollbar;

function microtime()
{
    return 2;
}

class AgentTest extends \PHPUnit_Framework_TestCase
{
    private $path = '/tmp/rollbar-php';

    protected function setUp()
    {
        if (!file_exists($this->path)) {
            mkdir($this->path);
        }
    }

    public function testAgent()
    {
        Rollbar\Rollbar::init(array(
            'access_token' => 'ad865e76e7fb496fab096ac07b1dbabb',
            'environment' => 'testing',
            'agent_log_location' => $this->path,
            'handler' => 'agent'
        ), false, false, false);
        $logger = Rollbar\Rollbar::logger();
        $logger->info("this is a test");
        $file = fopen($this->path . '/rollbar-relay.' . getmypid() . '.' . microtime(true) . '.rollbar', 'r');
        $line = fgets($file);
        $this->assertContains('this is a test', $line);
    }

    protected function tearDown()
    {
        $this->rrmdir($this->path);
    }

    private function rrmdir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir") {
                    $this->rrmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}
