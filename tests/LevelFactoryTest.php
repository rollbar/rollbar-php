<?php namespace Rollbar;

use Rollbar\Payload\Level;

class LevelFactoryTest extends BaseRollbarTest
{
    private LevelFactory $levelFactory;
    
    public function setUp(): void
    {
        $this->levelFactory = new LevelFactory();
    }
    
    /**
     * @dataProvider isValidLevelProvider
     */
    public function testIsValidLevelProvider($level, $expected): void
    {
        $this->assertEquals(
            $expected,
            $this->levelFactory->isValidLevel($level)
        );
    }
    
    public function isValidLevelProvider(): array
    {
        $data = $this->fromNameProvider();
        foreach ($data as &$testParams) {
            $testParams []= true;
        }
        $data []= array('test-stub', false);
        return $data;
    }
    
    /**
     * @dataProvider fromNameProvider
     */
    public function testFromName($level): void
    {
        $this->assertInstanceOf(
            Level::class,
            $this->levelFactory->fromName($level)
        );
    }
    
    public function fromNameProvider(): array
    {
        return array(
            array(Level::EMERGENCY),
            array(Level::ALERT),
            array(Level::CRITICAL),
            array(Level::ERROR),
            array(Level::WARNING),
            array(Level::NOTICE),
            array(Level::INFO),
            array(Level::DEBUG),
            array(Level::IGNORED),
            array(Level::IGNORE),
        );
    }
}
