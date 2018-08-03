<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Level;

class LevelTest extends BaseRollbarTest
{
    /**
     * @expectedException \Exception
     */
    public function testLevel()
    {
        $level = Level::TEST();

        $level = Level::CRITICAL();
        $this->assertNotNull($level);
        $this->assertSame(Level::CRITICAL(), $level);
        $this->assertSame(Level::critical(), $level);

        $level = Level::Info();
        $this->assertNotNull($level);
        $this->assertSame(Level::INFO(), $level);
        $this->assertSame('"info"', json_encode($level->serialize()));
    }
}
