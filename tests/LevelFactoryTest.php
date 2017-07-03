<?php namespace Rollbar;

use Rollbar\Payload\Level;

class LevelFactoryTest extends BaseRollbarTest
{
    private $levelFactory;
    
    public function setUp()
    {
        $this->levelFactory = new LevelFactory();
    }
    
    /**
     * @dataProvider isValidLevelProvider
     */
    public function testIsValidLevelProvider($level, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->levelFactory->isValidLevel($level)
        );
    }
    
    public function isValidLevelProvider()
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
    public function testFromName($level)
    {
        $this->assertInstanceOf(
            'Rollbar\Payload\Level',
            $this->levelFactory->fromName($level)
        );
    }
    
    public function fromNameProvider()
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
