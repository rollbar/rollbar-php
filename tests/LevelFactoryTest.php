<?php namespace Rollbar;

use Rollbar\Payload\Level;

class LevelFactoryTest extends BaseRollbarTest
{
    /**
     * @dataProvider isValidLevelProvider
     */
    public function testIsValidLevelProvider(string $level, bool $expected): void
    {
        self::assertSame(
            $expected,
            LevelFactory::isValidLevel($level)
        );
    }

    public function isValidLevelProvider(): array
    {
        $data = $this->fromNameProvider();
        foreach ($data as &$testParams) {
            $testParams[] = true;
        }
        $data[] = ['test-stub', false];
        return $data;
    }

    public function testFromNameInvalid(): void
    {
        self::assertNull(LevelFactory::fromName('not a level'));
    }

    /**
     * @dataProvider fromNameProvider
     */
    public function testFromName(string $level): void
    {
        self::assertInstanceOf(
            Level::class,
            LevelFactory::fromName($level)
        );
    }

    public function fromNameProvider(): array
    {
        return [
            [Level::EMERGENCY],
            [Level::ALERT],
            [Level::CRITICAL],
            [Level::ERROR],
            [Level::WARNING],
            [Level::NOTICE],
            [Level::INFO],
            [Level::DEBUG],
        ];
    }

    /**
     * @dataProvider fromNameOrInstanceProvider
     */
    public function testFromNameOrInstance(Level|string $level): void
    {
        self::assertInstanceOf(
            Level::class,
            LevelFactory::fromName($level)
        );
    }

    public function fromNameOrInstanceProvider(): array
    {
        return [
            [Level::EMERGENCY],
            [Level::ALERT],
            [LevelFactory::fromName(Level::CRITICAL)],
            [Level::ERROR],
            [Level::WARNING],
            [LevelFactory::fromName(Level::NOTICE)],
            [LevelFactory::fromName(Level::INFO)],
            [Level::DEBUG],
        ];
    }
}
