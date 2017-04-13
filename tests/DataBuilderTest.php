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
        $_SESSION = array();
        $this->dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests'
        ));
    }

    public function testMakeData()
    {
        $output = $this->dataBuilder->makeData(Level::fromName('error'), "testing", array());
        /**
         * @todo test for scrub data in makeData()
         */
        $this->assertEquals('tests', $output->getEnvironment());
    }

    public function testBranchKey()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'branch' => 'test-branch'
        ));

        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", array());
        $this->assertEquals('test-branch', $output->getServer()->getBranch());
    }

    public function testCodeVersion()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'code_version' => '3.4.1'
        ));
        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", array());
        $this->assertEquals('3.4.1', $output->getCodeVersion());
    }

    public function testHost()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'host' => 'my host'
        ));
        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", array());
        $this->assertEquals('my host', $output->getServer()->getHost());
    }

    public function testFramesWithoutContext()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'include_error_code_context' => false
        ));
        $output = $dataBuilder->makeFrames(new \Exception());
        $this->assertNull($output[0]->getContext());
    }

    public function testFramesWithContext()
    {

        $testFilePath = __DIR__ . '/DataBuilderTest.php';

        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'include_error_code_context' => true
        ));

        $backTrace = array(
            array(
                'file' => $testFilePath,
                'function' => 'testFramesWithoutContext'
            ),
            array(
                'file' => $testFilePath,
                'function' => 'testFramesWithContext'
            ),
        );

        $fh = fopen($testFilePath, 'r');
        $lineNumber = 0;
        while (!feof($fh)) {
            $lineNumber++;
            $line = fgets($fh);

            if ($line == '    public function testFramesWithoutContext()
') {
                $backTrace[0]['line'] = $lineNumber;
            } elseif ($line == '    public function testFramesWithContext()
') {
                $backTrace[1]['line'] = $lineNumber;
            }
        }
        fclose($fh);

        $output = $dataBuilder->makeFrames(new ErrorWrapper(null, null, null, null, $backTrace));
        $pre = $output[0]->getContext()->getPre();

        $expected = array();
        $fileContent = file($backTrace[0]['file']);
        for ($i = 7; $i > 1; $i--) {
            $expectedLine = $fileContent[$backTrace[0]['line']-$i];
            $expected[] = $expectedLine;
        }

        $this->assertEquals(
            str_replace(array("\r", "\n"), '', $expected),
            str_replace(array("\r", "\n"), '', $pre)
        );
    }

    public function testPerson()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'person' => array(
                'id' => '123',
                'email' => 'test@test.com'
            )
        ));
        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", array());
        $this->assertEquals('test@test.com', $output->getPerson()->getEmail());
    }

    public function testPersonFunc()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'person_fn' => function () {
                return array(
                    'id' => '123',
                    'email' => 'test@test.com'
                );
            }
        ));
        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", array());
        $this->assertEquals('test@test.com', $output->getPerson()->getEmail());
    }

    public function testRoot()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'root' => '/var/www/app'
        ));
        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", array());
        $this->assertEquals('/var/www/app', $output->getServer()->getRoot());
    }

    /**
     * @dataProvider scrubUrlDataProvider
     */
    public function testScrubUrl($testData, $scrubFields, $expected)
    {
        $result = DataBuilder::scrub($testData, $scrubFields);
        $this->assertEquals($expected, $result);
    }

    public function scrubUrlDataProvider()
    {
        return array(
            'nothing to scrub' => array(
                'https://rollbar.com', // $testData
                array(), // $scrubfields
                'https://rollbar.com' // $expected
            ),
            'mix of scrub and no scrub' => array(
                'https://rollbar.com?arg1=val1&arg2=val2&arg3=val3', // $testData
                array('arg2'),
                'https://rollbar.com?arg1=val1&arg2=xxxxxxxx&arg3=val3'
            ),
        );
    }
    
    /**
     * @dataProvider scrubDataProvider
     */
    public function testScrub($testData, $scrubFields, $expected)
    {
        $result = DataBuilder::scrub($testData, $scrubFields);
        $this->assertEquals($expected, $result, "Looks like some fields did not get scrubbed correctly.");
    }
    
    public function scrubDataProvider()
    {
        return array(
            'flat data array' => array(
                array( // $testData
                    'non sensitive data' => '123',
                    'sensitive data' => '456'
                ),
                array( // $scrubFields
                    'sensitive data'
                ),
                array( // $expected
                    'non sensitive data' => '123',
                    'sensitive data' => '********'
                )
            ),
            'recursive data array' => array(
                array( // $testData
                    'non sensitive data 1' => '123',
                    'non sensitive data 2' => '456',
                    'sensitive data' => '456',
                    array(
                        'non sensitive data 3' => '789',
                        'recursive sensitive data' => 'qwe',
                        'non sensitive data 3' => 'rty',
                        array(
                            'recursive sensitive data' => array(),
                        )
                    ),
                ),
                array( // $scrubFields
                    'sensitive data',
                    'recursive sensitive data'
                ),
                array( // $expected
                    'non sensitive data 1' => '123',
                    'non sensitive data 2' => '456',
                    'sensitive data' => '********',
                    array(
                        'non sensitive data 3' => '789',
                        'recursive sensitive data' => '********',
                        'non sensitive data 3' => 'rty',
                        array(
                            'recursive sensitive data' => '********',
                        )
                    ),
                ),
            ),
            'string encoded values' => array(
                // $testData
                http_build_query(
                    array(
                        'arg1' => 'val 1',
                        'sensitive' => 'scrubit',
                        'arg2' => 'val 3'
                    )
                ),
                array( // $scrubFields
                    'sensitive'
                ),
                // $expected
                http_build_query(
                    array(
                        'arg1' => 'val 1',
                        'sensitive' => 'xxxxxxxx',
                        'arg2' => 'val 3'
                    )
                ),
            ),
            'string encoded recursive values' => array(
                // $testData
                http_build_query(
                    array(
                        'arg1' => 'val 1',
                        'sensitive' => 'scrubit',
                        'arg2' => array(
                            'arg3' => 'val 3',
                            'sensitive' => 'scrubit'
                        )
                    )
                ),
                array( // $scrubFields
                    'sensitive'
                ),
                // $expected
                http_build_query(
                    array(
                        'arg1' => 'val 1',
                        'sensitive' => 'xxxxxxxx',
                        'arg2' => array(
                            'arg3' => 'val 3',
                            'sensitive' => 'xxxxxxxx'
                        )
                    )
                ),
            ),
            'string encoded recursive values in recursive array' => array(
                array( // $testData
                    'non sensitive data 1' => '123',
                    'non sensitive data 2' => '456',
                    'sensitive data' => '456',
                    array(
                        'non sensitive data 3' => '789',
                        'recursive sensitive data' => 'qwe',
                        'non sensitive data 3' => http_build_query(
                            array(
                                'arg1' => 'val 1',
                                'sensitive' => 'scrubit',
                                'arg2' => array(
                                    'arg3' => 'val 3',
                                    'sensitive' => 'scrubit'
                                )
                            )
                        ),
                        array(
                            'recursive sensitive data' => array(),
                        )
                    ),
                ),
                array( // $scrubFields
                    'sensitive data',
                    'recursive sensitive data',
                    'sensitive'
                ),
                array( // $expected
                    'non sensitive data 1' => '123',
                    'non sensitive data 2' => '456',
                    'sensitive data' => '********',
                    array(
                        'non sensitive data 3' => '789',
                        'recursive sensitive data' => '********',
                        'non sensitive data 3' => http_build_query(
                            array(
                                'arg1' => 'val 1',
                                'sensitive' => 'xxxxxxxx',
                                'arg2' => array(
                                    'arg3' => 'val 3',
                                    'sensitive' => 'xxxxxxxx'
                                )
                            )
                        ),
                        array(
                            'recursive sensitive data' => '********',
                        )
                    ),
                )
            )
        );
    }

    /**
     * @dataProvider scrubArrayDataProvider
     */
    public function testScrubArray($testData, $scrubFields, $expected)
    {
        $result = DataBuilder::scrubArray($testData, $scrubFields);
        $this->assertEquals($expected, $result, "Looks like some fields did not get scrubbed correctly.");
    }

    public function scrubArrayDataProvider()
    {
        return array(
            'flat data array' => array(
                array( // $testData
                    'non sensitive data' => '123',
                    'sensitive data' => '456'
                ),
                array( // $scrubFields
                    'sensitive data'
                ),
                array( // $expected
                    'non sensitive data' => '123',
                    'sensitive data' => '********'
                )
            ),
            'recursive data array' => array(
                array( // $testData
                    'non sensitive data 1' => '123',
                    'non sensitive data 2' => '456',
                    'sensitive data' => '456',
                    array(
                        'non sensitive data 3' => '789',
                        'recursive sensitive data' => 'qwe',
                        'non sensitive data 3' => 'rty',
                        array(
                            'recursive sensitive data' => array(),
                        )
                    ),
                ),
                array( // $scrubFields
                    'sensitive data',
                    'recursive sensitive data'
                ),
                array( // $expected
                    'non sensitive data 1' => '123',
                    'non sensitive data 2' => '456',
                    'sensitive data' => '********',
                    array(
                        'non sensitive data 3' => '789',
                        'recursive sensitive data' => '********',
                        'non sensitive data 3' => 'rty',
                        array(
                            'recursive sensitive data' => '********',
                        )
                    ),
                ),
            )
        );
    }

    public function testScrubReplacement()
    {
        $testData = array('scrubit' => '123');
        $result = DataBuilder::scrubArray(
            $testData,
            array('scrubit'),
            "@"
        );

        $this->assertEquals("@@@@@@@@", $result['scrubit']);
    }
}
