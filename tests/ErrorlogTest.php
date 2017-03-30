<?php

namespace Rollbar;

class ErrorlogTest extends \PHPUnit_Framework_TestCase
{
    private $path = '/tmp/rollbar-php';
    private $error_log = null;

    protected function setUp()
    {
        if (!file_exists($this->path)) {
            mkdir($this->path);
        }
    }

    public function testErrorlog()
    {
        $this->error_log = ini_set('error_log', $this->path . '/rollbar-errorlog.rollbar');
        Rollbar::init(array(
            'access_token' => 'ad865e76e7fb496fab096ac07b1dbabb',
            'environment' => 'testing',
            'handler' => 'errorlog'
        ), false, false, false);
        $logger = Rollbar::logger();
        $logger->info("this is a test");
        $file = fopen($this->path . '/rollbar-errorlog.rollbar', 'r');
        $line = fgets($file);
        $this->assertContains('this is a test', $line);
    }

    protected function tearDown()
    {
        $this->rrmdir($this->path);
        ini_set('error_log', $this->error_log);
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
