<?php

namespace Rollbar;

use Rollbar\Payload\Level;

class DataBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DataBuilder
     */
    private $dataBuilder;

    public function setUp()
    {
        $this->dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests'
        ));
    }

    public function testMakeData()
    {
        $output = $this->dataBuilder->makeData(Level::fromName('error'), "testing", array());
        $this->assertEquals('tests', $output->getEnvironment());
    }
}
