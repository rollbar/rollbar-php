<?php

namespace Rollbar;

use Rollbar\Payload\Level;
use Rollbar\TestHelpers\MockPhpStream;

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
        $this->assertEquals('tests', $output->getEnvironment());
    }
    
    /**
     * @dataProvider getUrlProvider
     */
    public function testGetUrl($protoData, $hostData, $portData, $dataName)
    {
        // Set up proto
        $pre_SERVER = $_SERVER;
        
        $_SERVER = array_merge(
            $_SERVER,
            $protoData[0],
            $hostData[0],
            $portData[0]
        );
        $expectedProto = $protoData[1];
        $expectedHost = $hostData[1];
        $expectedPort = $portData[1];
        $expectedPort = ($expectedPort == 80 || $expectedPort == 443) ? "" : $expectedPort;
        
        $expected = '';
        $expected = $expectedProto . "://" . $expectedHost .
                    ($expectedPort ? $expected  . ':' . $expectedPort : $expected) .
                    '/';
                    
        if ($expectedHost == 'unknown') {
            $expected = null;
        }
        
        // When DataBuilder builds the data
        $response = $this->dataBuilder->makeData(Level::fromName('error'), "testing", array());
        $result = $response->getRequest()->getUrl();
        
        $_SERVER = $pre_SERVER;
        
        $this->assertEquals($expected, $result);
    }
    
    public function getUrlProvider()
    {
        $protoData = $this->getUrlProtoProvider();
        $hostData = $this->getUrlHostProvider();
        $portData = $this->getUrlPortProvider();
        
        $testData = array();
        
        $dataName = 0;
        
        foreach ($protoData as $protoTest) {
            foreach ($hostData as $hostTest) {
                foreach ($portData as $portTest) {
                    if ($dataName >= 96 && $dataName <= 99) {
                        continue;
                    }
                    
                    $testData []= array(
                        $protoTest, // test param 1
                        $hostTest, // test param 2
                        $portTest, // test param 3,
                        $dataName // test param 4
                    );
                    
                    $dataName++;
                }
            }
        }
        
        return $testData;
    }
    
    /**
     * @dataProvider parseForwardedStringProvider
     */
    public function testParseForwardedString($forwaded, $expected)
    {
        $output = $this->dataBuilder->parseForwardedString($forwaded);
        $this->assertEquals($expected, $output);
    }
    
    public function parseForwardedStringProvider()
    {
        return array(
            array( // test 1
                'Forwarded: for="_mdn" ',
                array(
                    'for' => array('"_mdn"')
                )
            ),
            array( // test 2
                'Forwarded: for="_mdn", for="_mdn2" ',
                array(
                    'for' => array('"_mdn"', '"_mdn2"')
                )
            ),
            array( // test 3
                'Forwarded: For="[2001:db8:cafe::17]:4711"',
                array(
                    'for' => array('"[2001:db8:cafe::17]:4711"')
                )
            ),
            array( // test 4
                'Forwarded: for=192.0.2.60; proto=http; by=203.0.113.43',
                array(
                    'for' => array('192.0.2.60'),
                    'by' => array('203.0.113.43'),
                    'proto' => 'http'
                )
            ),
            array( // test 5
                'Forwarded: for=192.0.2.43, for=198.51.100.17;'.
                           'by=192.0.2.44, by=198.51.100.18',
                array(
                    'for' => array('192.0.2.43','198.51.100.17'),
                    'by' => array('192.0.2.44','198.51.100.18')
                )
            ),
            array( // test 6
                'Forwarded: for=192.0.2.60; host=hostname; by=203.0.113.43; proto=https',
                array(
                    'for' => array('192.0.2.60'),
                    'by' => array('203.0.113.43'),
                    'host' => 'hostname',
                    'proto' => 'https'
                )
            )
        );
    }
    
    /**
     * @dataProvider getUrlProtoProvider
     */
    public function testGetUrlProto($data, $expected)
    {
        $pre_SERVER = $_SERVER;
        $_SERVER = array_merge($_SERVER, $data);
        
        $output = $this->dataBuilder->getUrlProto();
        
        $this->assertEquals($expected, $output);
        
        $_SERVER = $pre_SERVER;
    }
    
    public function getUrlProtoProvider()
    {
        return array(
            array( // test 1: HTTP_FORWARDED
                array(
                    'HTTP_FORWARDED' => 'Forwarded: for=192.0.2.60; proto=http; by=203.0.113.43',
                ),
                'http'
            ),
            array( // test 2: HTTP_X_FORWARDED
                array(
                    'HTTP_X_FORWARDED_PROTO' => 'http',
                ),
                'http'
            ),
            array( // test 3: HTTPS server global
                array(
                    'HTTPS' => 'on',
                ),
                'https'
            ),
            array( // test 4: default
                array(),
                'http'
            ),
            array( // test 5: HTTP_FORWARDED https
                array(
                    'HTTP_FORWARDED' => 'Forwarded: for=192.0.2.60; proto=https; by=203.0.113.43',
                ),
                'https'
            ),
        );
    }
    
    /**
     * @dataProvider getUrlHostProvider
     */
    public function testGetUrlHost($data, $expected)
    {
        $pre_SERVER = $_SERVER;
        $_SERVER = array_merge($_SERVER, $data);
        
        $output = $this->dataBuilder->getUrlHost();
        
        $_SERVER = $pre_SERVER;
        
        $this->assertEquals($expected, $output);
    }
    
    public function getUrlHostProvider()
    {
        return array(
            array( // test 1: HTTP_FORWARDED
                array(
                    'HTTP_FORWARDED' => 'Forwarded: for=192.0.2.60; host=test-hostname.com; by=203.0.113.43',
                ),
                'test-hostname.com'
            ),
            array( // test 2: HTTP_X_FORWARDED
                array(
                    'HTTP_X_FORWARDED_HOST' => 'test-hostname.com',
                ),
                'test-hostname.com'
            ),
            array( // test 3: HTTP_HOST server global
                array(
                    'HTTP_HOST' => 'test-hostname.com',
                ),
                'test-hostname.com'
            ),
            array( // test 4: default
                array(),
                'unknown'
            ),
            array( // test 5: SERVER_name
                array(
                    'SERVER_NAME' => 'test-hostname.com',
                ),
                'test-hostname.com'
            ),
            array( // test 6: HTTP_HOST server global with port
                array(
                    'HTTP_HOST' => 'test-hostname.com:8080',
                ),
                'test-hostname.com'
            ),
        );
    }
    
    /**
     * @dataProvider getUrlPortProvider
     */
    public function testGetUrlPort($data, $expected)
    {
        $pre_SERVER = $_SERVER;
        $_SERVER = array_merge($_SERVER, $data);
        
        $output = $this->dataBuilder->getUrlPort(
            isset($_SERVER['$proto']) ? $_SERVER['$proto'] : null
        );
        
        $_SERVER = $pre_SERVER;
        
        $this->assertEquals($expected, $output);
    }
    
    public function getUrlPortProvider()
    {
        return array(
            array( // test 1: HTTP_X_FORWARDED
                array(
                    'HTTP_X_FORWARDED_PORT' => '8080',
                ),
                8080
            ),
            array( // test 2: SERVER_PORT server global
                array(
                    'SERVER_PORT' => '8080',
                ),
                8080
            ),
            array( // test 3: default
                array(),
                80
            ),
            array( // test 4: $proto param
                array(
                    '$proto' => 'https',
                ),
                443
            )
        );
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
    
    public function testGetMessage()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests'
        ));
        
        $result = $dataBuilder->makeData(Level::fromName('error'), "testing", array());
        $this->assertNull($result->getBody()->getValue()->getBacktrace());
        
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'send_message_trace' => true
        ));
    
        $result = $dataBuilder->makeData(Level::fromName('error'), "testing", array());
        $this->assertNotEmpty($result->getBody()->getValue()->getBacktrace());
    }

    public function testExceptionFramesWithoutContext()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'include_error_code_context' => true,
            'include_exception_code_context' => false
        ));
        $output = $dataBuilder->makeFrames(new \Exception());
        $this->assertNull($output[1]->getContext());
    }

    public function testExceptionFramesWithoutContextDefault()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests'
        ));
        $output = $dataBuilder->makeFrames(new \Exception());
        $this->assertNull($output[1]->getContext());
    }

    public function testExceptionFramesWithContext()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'include_exception_code_context' => true
        ));
        $output = $dataBuilder->makeFrames(new \Exception());
        $this->assertNotEmpty($output[1]->getContext());
    }

    public function testFramesWithoutContext()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'include_error_code_context' => false,
            'include_exception_code_context' => true
        ));
        $backtrace = array(array());
        $output = $dataBuilder->makeFrames(new ErrorWrapper(null, null, null, null, $backtrace), true);
        $this->assertNull($output[0]->getContext());
    }

    public function testFramesWithContext()
    {

        $testFilePath = __DIR__ . '/DataBuilderTest.php';

        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'include_error_code_context' => true,
            'include_exception_code_context' => false
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

        $file = fopen($testFilePath, 'r');
        $lineNumber = 0;
        while (!feof($file)) {
            $lineNumber++;
            $line = fgets($file);

            if ($line == '    public function testFramesWithoutContext()
') {
                $backTrace[0]['line'] = $lineNumber;
            } elseif ($line == '    public function testFramesWithContext()
') {
                $backTrace[1]['line'] = $lineNumber;
            }
        }
        fclose($file);

        $output = $dataBuilder->makeFrames(new ErrorWrapper(null, null, null, null, $backTrace), true);
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

    public function testFramesWithoutContextDefault()
    {
        $testFilePath = __DIR__ . '/DataBuilderTest.php';

        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests'
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

        $file = fopen($testFilePath, 'r');
        $lineNumber = 0;
        while (!feof($file)) {
            $lineNumber++;
            $line = fgets($file);

            if ($line == '    public function testFramesWithoutContext()
') {
                $backTrace[0]['line'] = $lineNumber;
            } elseif ($line == '    public function testFramesWithContext()
') {
                $backTrace[1]['line'] = $lineNumber;
            }
        }
        fclose($file);

        $output = $dataBuilder->makeFrames(new ErrorWrapper(null, null, null, null, $backTrace));
        $this->assertNull($output[0]->getContext());
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
    
    public function testPersonFuncException()
    {
        $logger = \Rollbar\Rollbar::scope(array(
            'access_token' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'person_fn' => function () {
                throw new \Exception("Exception from person_fn");
            }
        ));
        
        try {
            $logger->log(Level::fromName('error'), "testing exceptions in person_fn", array());
            $this->assertTrue(true); // assert that exception was not thrown
        } catch (\Exception $exception) {
            $this->fail("Exception in person_fn was not caught.");
        }
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
                array('arg2'), // $scrubFields
                'https://rollbar.com?arg1=val1&arg2=xxxxxxxx&arg3=val3' // $expected
            ),
        );
    }
    
    /**
     * @dataProvider scrubDataProvider
     */
    public function testScrub($testData, $scrubFields, $expected)
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'scrubFields' => $scrubFields
        ));
        $result = $dataBuilder->scrub($testData);
        $this->assertEquals($expected, $result, "Looks like some fields did not get scrubbed correctly.");
    }
    
    public function scrubDataProvider()
    {
        return array_merge(array(
            'flat data array' =>
                $this->scrubFlatDataProvider(),
            'recursive data array' =>
                $this->scrubRecursiveDataProvider(),
            'string encoded values' =>
                $this->scrubFlatStringDataProvider(),
            'string encoded recursive values' =>
                $this->scrubRecursiveStringDataProvider(),
            'string encoded recursive values in recursive array' =>
                $this->scrubRecursiveStringRecursiveDataProvider()
        ), $this->scrubUrlDataProvider(), $this->scrubJSONNumbersProvider());
    }

    private function scrubJSONNumbersProvider()
    {
        return array(
            'plain array' => array(
                  '[1023,1924]',
                  array(
                      'sensitive'
                  ),
                  '[1023,1924]'
            ),
            'param equals array' => array(
                'b=[1023,1924]',
                array(
                    'sensitive'
                ),
                'b=[1023,1924]'
            )
        );
    }

    private function scrubFlatDataProvider()
    {
        return array(
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
        );
    }
    
    private function scrubRecursiveDataProvider()
    {
        return array(
            array( // $testData
                'non sensitive data 1' => '123',
                'non sensitive data 2' => '456',
                'non sensitive data 3' => '4&56',
                'non sensitive data 4' => 'a=4&56',
                'non sensitive data 6' => '?baz&foo=bar',
                'sensitive data' => '456',
                array(
                    'non sensitive data 3' => '789',
                    'non sensitive data 5' => '789&5=',
                    'recursive sensitive data' => 'qwe',
                    'non sensitive data 3' => 'rty',
                    array(
                        'recursive sensitive data' => array(),
                    )
                ),
            ),
            array( // $scrubFields
                'sensitive data',
                'recursive sensitive data',
                'foo'
            ),
            array( // $expected
                'non sensitive data 1' => '123',
                'non sensitive data 2' => '456',
                'non sensitive data 3' => '4&56',
                'non sensitive data 4' => 'a=4&56',
                'non sensitive data 6' => '?baz=&foo=xxxxxxxx',
                'sensitive data' => '********',
                array(
                    'non sensitive data 3' => '789',
                    'non sensitive data 5' => '789&5=',
                    'recursive sensitive data' => '********',
                    'non sensitive data 3' => 'rty',
                    array(
                        'recursive sensitive data' => '********',
                    )
                ),
            ),
        );
    }
    
    private function scrubFlatStringDataProvider()
    {
        return array(
            // $testData
            '?' . http_build_query(
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
            '?' . http_build_query(
                array(
                    'arg1' => 'val 1',
                    'sensitive' => 'xxxxxxxx',
                    'arg2' => 'val 3'
                )
            ),
        );
    }
    
    private function scrubRecursiveStringDataProvider()
    {
        return array(
            // $testData
            '?' . http_build_query(
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
            '?' . http_build_query(
                array(
                    'arg1' => 'val 1',
                    'sensitive' => 'xxxxxxxx',
                    'arg2' => array(
                        'arg3' => 'val 3',
                        'sensitive' => 'xxxxxxxx'
                    )
                )
            ),
        );
    }
    
    private function scrubRecursiveStringRecursiveDataProvider()
    {
        return array(
            array( // $testData
                'non sensitive data 1' => '123',
                'non sensitive data 2' => '456',
                'sensitive data' => '456',
                array(
                    'non sensitive data 3' => '789',
                    'recursive sensitive data' => 'qwe',
                    'non sensitive data 3' => '?' . http_build_query(
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
                    'non sensitive data 3' => '?' . http_build_query(
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
        );
    }

    /**
     * @dataProvider scrubArrayDataProvider
     */
    public function testScrubArray($testData, $scrubFields, $expected)
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'scrubFields' => $scrubFields
        ));
        $result = $dataBuilder->scrub($testData);
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
        
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'scrubFields' => array('scrubit')
        ));
        
        $result = $dataBuilder->scrub($testData, "@");

        $this->assertEquals("@@@@@@@@", $result['scrubit']);
    }
    
    public function testSetRequestBody()
    {
        $_POST['arg1'] = "val1";
        $_POST['arg2'] = "val2";
        $streamInput = http_build_query($_POST);
        
        stream_wrapper_unregister("php");
        stream_wrapper_register("php", "\Rollbar\TestHelpers\MockPhpStream");

        file_put_contents('php://input', $streamInput);
        
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests'
        ));
        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", array());
        $requestBody = $output->getRequest()->getBody();
        
        $this->assertEquals($streamInput, $requestBody);
        
        stream_wrapper_restore("php");
    }
}
