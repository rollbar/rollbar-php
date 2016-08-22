<?php namespace Rollbar;

use \Mockery as m;
use Rollbar\Payload\Level;

class LevelTest extends \PHPUnit_Framework_TestCase
{
    public function testLevel()
    {
        $l = Level::TEST();
        $this->assertNull($l);

        $l = Level::CRITICAL();
        $this->assertNotNull($l);
        $this->assertSame(Level::CRITICAL(), $l);
        $this->assertSame(Level::critical(), $l);

        $l = Level::Info();
        $this->assertNotNull($l);
        $this->assertSame(Level::INFO(), $l);
        $this->assertSame('"info"', json_encode($l->jsonSerialize()));
    }
}
