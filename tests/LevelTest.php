<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Level;

class LevelTest extends \PHPUnit_Framework_TestCase
{
    public function testLevel()
    {
        try {
            $level = Level::TEST();
            $this->fail();
        } catch(\Exception $exception) {
            $this->assertTrue(true);
        }
        

        $level = Level::CRITICAL();
        $this->assertNotNull($level);
        $this->assertSame(Level::CRITICAL(), $level);
        $this->assertSame(Level::critical(), $level);

        $level = Level::Info();
        $this->assertNotNull($level);
        $this->assertSame(Level::INFO(), $level);
        $this->assertSame('"info"', json_encode($level->jsonSerialize()));
    }
}
