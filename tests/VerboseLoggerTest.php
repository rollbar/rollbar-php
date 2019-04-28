<?php namespace Rollbar;

use \Mockery as m;

class VerboseLoggerTest extends BaseRollbarTest
{
    public function testLog()
    {
        // verbose == VERBOSE_NONE
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => "test",
            'verbose' => Config::VERBOSE_NONE
        ));
        $handler = m::mock('\Monolog\Handler\AbstractHandler')->makePartial();
        $handler->shouldNotReceive('handle');
        $subject = new VerboseLogger('verbose', $config, array($handler));
        $this->assertFalse($subject->info("test log"));

        // verbose == INFO
        $handler->shouldReceive('handle');
        $config->configure(array('verbose' => \Psr\Log\LogLevel::INFO));
        $this->assertTrue($subject->info("test log"));
    }
}