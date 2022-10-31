<?php namespace Rollbar;

use Rollbar\Payload\Level;

class LevelTest extends BaseRollbarTest
{
    private Level $level;

    public function setUp(): void
    {
        $this->level = (new LevelFactory())->fromName(Level::ERROR);
    }

    public function testInvalidLevelThrowsAnException(): void
    {
        self::expectException(\Error::class);
        $level = Level::TEST();
    }

    public function testLevel(): void
    {
        $level = Level::CRITICAL;
        self::assertNotNull($level);
        self::assertSame(Level::CRITICAL, $level);
    }

    public function testStringable(): void
    {
        self::assertSame('error', (string)$this->level);
    }

    public function testToInt(): void
    {
        self::assertSame(10000, $this->level->toInt());
    }

    public function testSerialize(): void
    {
        self::assertSame('error', $this->level->serialize());
    }
}
