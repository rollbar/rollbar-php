<?php

namespace Rollbar;

use Rollbar\Payload\Level;
use Rollbar\TestHelpers\MockPhpStream;

class DataBuilderTest extends BaseRollbarTest
{

    /**
     * @var DataBuilder
     */
    private $dataBuilder;

    public function setUp()
    {
        $_SESSION = array();
        
        $this->dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
    }

    public function testMakeData()
    {
        $output = $this->dataBuilder->makeData(Level::ERROR, "testing", array());
        $this->assertEquals('tests', $output->getEnvironment());
    }
    
    /**
     * @dataProvider getUrlProvider
     */
    public function testGetUrl($protoData, $hostData, $portData)
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
        $response = $this->dataBuilder->makeData(Level::ERROR, "testing", array());
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
                        $portTest // test param 3,
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
            array( // test 2: HTTP_X_FORWARDED with commas
                array(
                    'HTTP_X_FORWARDED_PROTO' => 'http,https',
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

    public function testGetHeaders()
    {
        $pre_SERVER = $_SERVER;
        $_SERVER = array(
            'HTTP_ACCEPT' => 'blah/blah',
            'HTTP_USER_AGENT' => 'fake 2.0',
            'REMOTE_USER' => 'bob',
        );
        $expected = array(
            'Accept' => 'blah/blah',
            'User-Agent' => 'fake 2.0',
        );
        $output = $this->dataBuilder->getHeaders();
        $_SERVER = $pre_SERVER;
        $this->assertEquals($expected, $output);
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
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'branch' => 'test-branch',
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));

        $output = $dataBuilder->makeData(Level::ERROR, "testing", array());
        $this->assertEquals('test-branch', $output->getServer()->getBranch());
    }

    public function testCodeVersion()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'code_version' => '3.4.1',
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
        $output = $dataBuilder->makeData(Level::ERROR, "testing", array());
        $this->assertEquals('3.4.1', $output->getCodeVersion());
    }

    public function testHost()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'host' => 'my host',
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
        $output = $dataBuilder->makeData(Level::ERROR, "testing", array());
        $this->assertEquals('my host', $output->getServer()->getHost());
    }
    
    public function testGetMessage()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
        
        $result = $dataBuilder->makeData(Level::ERROR, "testing", array());
        $this->assertNull($result->getBody()->getValue()->getBacktrace());
    }
    
    public function testGetMessageSendMessageTrace()
    {
        
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'send_message_trace' => true,
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
    
        $result = $dataBuilder->makeData(Level::ERROR, "testing", array());
        $this->assertNotEmpty($result->getBody()->getValue()->getBacktrace());
    }
    
    public function testGetMessageTraceArguments()
    {
        // Negative test
        $c = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'send_message_trace' => true
        ));
        $dataBuilder = $c->getDataBuilder();
        $expected = 'testing';
    
        $result = $dataBuilder->makeData(Level::ERROR, $expected, array());
        $frames = $result->getBody()->getValue()->getBacktrace();
        
        $this->assertEquals(
            $expected,
            $frames[0]['args'][0],
            "Arguments in stack frames NOT included when they should be."
        );
        
        // Positive test
        $c = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'send_message_trace' => true,
            'local_vars_dump' => false
        ));
        $dataBuilder = $c->getDataBuilder();
    
        $result = $dataBuilder->makeData(Level::ERROR, $expected, array());
        $frames = $result->getBody()->getValue()->getBacktrace();
        
        $this->assertArrayNotHasKey(
            'args',
            $frames[0],
            "Arguments in stack frames included when they should have not been."
        );
    }
    
    public function testExceptionTraceArguments()
    {
        // Negative test
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
        $exception = $this->exceptionTraceArgsHelper('trace args message');
        $frames = $dataBuilder->getExceptionTrace($exception)->getFrames();
        $this->assertNull(
            $frames[count($frames)-1]->getArgs(),
            "Frames arguments available in trace when they should not be."
        );
        
        // Positive test
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'local_vars_dump' => true,
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
        $expected = 'trace args message';
        $exception = $this->exceptionTraceArgsHelper($expected);
        $frames = $dataBuilder->getExceptionTrace($exception)->getFrames();
        $args = $frames[count($frames)-2]->getArgs();
        
        $this->assertEquals(
            $expected,
            $args[0],
            "Frames arguments NOT available in trace when they should be."
        );
    }
    
    /**
     * The purpose of this method is to provide a frame with an expected
     * argument in testExceptionTraceArguments.
     *
     * @param string $message Argument expected in the last frame of the trace
     *
     * @return \Exception
     */
    private function exceptionTraceArgsHelper($message)
    {
        return new \Exception($message);
    }

    public function testExceptionFramesWithoutContext()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'include_error_code_context' => true,
            'include_exception_code_context' => false,
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
        $output = $dataBuilder->getExceptionTrace(new \Exception())->getFrames();
        $this->assertNull($output[1]->getContext());
    }

    public function testExceptionFramesWithoutContextDefault()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
        $output = $dataBuilder->getExceptionTrace(new \Exception())->getFrames();
        $this->assertNull($output[1]->getContext());
    }

    public function testExceptionFramesWithContext()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'include_exception_code_context' => true,
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
        $output = $dataBuilder->getExceptionTrace(new \Exception())->getFrames();
        $this->assertNotEmpty($output[count($output)-1]->getContext());
    }

    public function testFramesWithoutContext()
    {
        $utilities = new Utilities;
        
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'include_error_code_context' => false,
            'include_exception_code_context' => true,
            'levelFactory' => new LevelFactory,
            'utilities' => $utilities
        ));
        $testFilePath = __DIR__ . '/DataBuilderTest.php';
        $backtrace = array(
            array(
                'file' => $testFilePath,
                'function' => 'testFramesWithoutContext',
                'line' => 42
            ),
            array(
                'file' => $testFilePath,
                'function' => 'testFramesWithContext',
                'line' => 99
            ),
        );
        $output = $dataBuilder->getErrorTrace(
            new ErrorWrapper(
                E_ERROR,
                'bork',
                null,
                null,
                $backtrace,
                $utilities
            )
        )->getFrames();
        
        $this->assertNull($output[0]->getContext());
    }

    public function testFramesWithContext()
    {
        $utilities = new Utilities;

        $testFilePath = __DIR__ . '/DataBuilderTest.php';

        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'include_error_code_context' => true,
            'include_exception_code_context' => false,
            'levelFactory' => new LevelFactory,
            'utilities' => $utilities
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

        $output = $dataBuilder->getErrorTrace(
            new ErrorWrapper(
                E_ERROR,
                'bork',
                null,
                null,
                $backTrace,
                $utilities
            )
        )->getFrames();
        $pre = $output[1]->getContext()->getPre();

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
        
        $utilities = new Utilities;

        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'levelFactory' => new LevelFactory,
            'utilities' => $utilities
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

        $output = $dataBuilder->getErrorTrace(
            new ErrorWrapper(
                E_ERROR,
                'bork',
                null,
                null,
                $backTrace,
                $utilities
            )
        )->getFrames();
        $this->assertNull($output[0]->getContext());
    }

    public function testPerson()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'person' => array(
                'id' => '123',
                'username' => 'tester',
                'email' => 'test@test.com'
            ),
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
        $output = $dataBuilder->makeData(Level::ERROR, "testing", array());
        $this->assertEquals('123', $output->getPerson()->getId());
        $this->assertNull($output->getPerson()->getUsername());
        $this->assertNull($output->getPerson()->getEmail());
    }
    
    public function testPersonCaptureEmailUsername()
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'person' => array(
                'id' => '123',
                'username' => 'tester',
                'email' => 'test@test.com'
            ),
            'capture_email' => true,
            'capture_username' => true
        ));
        $dataBuilder = $config->getDataBuilder();
        
        $output = $dataBuilder->makeData(Level::ERROR, "testing", array());
        
        $this->assertEquals('123', $output->getPerson()->getId());
        $this->assertEquals('tester', $output->getPerson()->getUsername());
        $this->assertEquals('test@test.com', $output->getPerson()->getEmail());
    }

    public function testPersonFunc()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'person_fn' => function () {
                return array(
                    'id' => '123',
                    'email' => 'test@test.com'
                );
            },
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
        $output = $dataBuilder->makeData(Level::ERROR, "testing", array());
        $this->assertEquals('123', $output->getPerson()->getId());
    }
    
    public function testPersonFuncException()
    {
        \Rollbar\Rollbar::init(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => 'tests'
        ));
        $logger = \Rollbar\Rollbar::scope(array(
            'person_fn' => function () {
                throw new \Exception("Exception from person_fn");
            }
        ));
        
        try {
            $logger->log(Level::ERROR, "testing exceptions in person_fn", array());
            $this->assertTrue(true); // assert that exception was not thrown
        } catch (\Exception $exception) {
            $this->fail("Exception in person_fn was not caught.");
        }
    }

    public function testRoot()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'root' => '/var/www/app',
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
        $output = $dataBuilder->makeData(Level::ERROR, "testing", array());
        $this->assertEquals('/var/www/app', $output->getServer()->getRoot());
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
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities,
            'include_raw_request_body' => true,
        ));
        $output = $dataBuilder->makeData(Level::ERROR, "testing", array());
        $requestBody = $output->getRequest()->getBody();
        
        $this->assertEquals($streamInput, $requestBody);
        if (version_compare(PHP_VERSION, '5.6.0') < 0) {
            $this->assertEquals($streamInput, $_SERVER['php://input']);
        }
        
        stream_wrapper_restore("php");
    }
    
    public function testPostDataPutRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        
        $expected = 'val1';
        $streamInput = http_build_query(array(
            'arg1' => $expected
        ));
        
        stream_wrapper_unregister("php");
        stream_wrapper_register("php", "\Rollbar\TestHelpers\MockPhpStream");

        file_put_contents('php://input', $streamInput);
        
        $config = array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities,
            'include_raw_request_body' => true
        );
        
        $dataBuilder = new DataBuilder($config);
        
        $data = $dataBuilder->makeData(Level::ERROR, "testing", array());
        $post = $data->getRequest()->getPost();
        
        $this->assertEquals($expected, $post['arg1']);
        
        unset($_SERVER['REQUEST_METHOD']);
        stream_wrapper_restore("php");
    }
    
    public function testGenerateErrorWrapper()
    {
        $result = $this->dataBuilder->generateErrorWrapper(E_ERROR, 'bork', null, null);
        
        $this->assertTrue($result instanceof ErrorWrapper);
    }

    /**
     * @dataProvider captureErrorStacktracesProvider
     */
    public function testCaptureErrorStacktracesException(
        $captureErrorStacktraces,
        $expected
    ) {
    
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'capture_error_stacktraces' => $captureErrorStacktraces,
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
        
        $result = $dataBuilder->makeData(
            Level::ERROR,
            new \Exception(),
            array()
        );
        $frames = $result->getBody()->getValue()->getFrames();
        
        $this->assertEquals($expected, count($frames) === 0);
    }
    
    public function testFramesOrder()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'include_exception_code_context' => true,
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
        $frames = $dataBuilder->makeFrames(new \Exception(), false);
        $this->assertStringEndsWith(
            'tests/DataBuilderTest.php',
            $frames[count($frames)-1]->getFilename()
        );
        $this->assertEquals(882, $frames[count($frames)-1]->getLineno());
        $this->assertEquals('Rollbar\DataBuilderTest::testFramesOrder', $frames[count($frames)-2]->getMethod());
    }
    
    /**
     * @dataProvider captureErrorStacktracesProvider
     */
    public function testCaptureErrorStacktracesError(
        $captureErrorStacktraces,
        $expected
    ) {
    
        $dataBuilder = new DataBuilder(array(
            'accessToken' => $this->getTestAccessToken(),
            'environment' => 'tests',
            'capture_error_stacktraces' => $captureErrorStacktraces,
            'levelFactory' => new LevelFactory,
            'utilities' => new Utilities
        ));
        
        $result = $dataBuilder->generateErrorWrapper(E_ERROR, 'bork', null, null);
        $frames = $result->getBacktrace();
        
        $this->assertEquals($expected, count($frames) == 0);
    }
    
    public function captureErrorStacktracesProvider()
    {
        return array(
            array(false,true),
            array(true,false)
        );
    }
    
    /**
     * @dataProvider getUserIpProvider
     */
    public function testGetUserIp($ipAddress, $expected, $captureIP)
    {
        $_SERVER['REMOTE_ADDR'] = $ipAddress;
        
        $config = array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => 'tests'
        );
        
        if ($captureIP !== null) {
            $config['capture_ip'] = $captureIP;
        }
        
        $config = new Config($config);
        
        $dataBuilder = $config->getDataBuilder();
        $output = $dataBuilder->makeData(Level::ERROR, "testing", array());
        
        $this->assertEquals($expected, $output->getRequest()->getUserIp());
        
        unset($_SERVER['REMOTE_ADDR']);
        
        $_SERVER['HTTP_X_FORWARDED_FOR'] = $ipAddress;
        $_SERVER['REMOTE_ADDR'] = 'dont use this, this time';
        $output = $dataBuilder->makeData(Level::ERROR, "testing", array());
        $this->assertEquals($expected, $output->getRequest()->getUserIp());
        
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        
        $_SERVER['HTTP_X_REAL_IP'] = $ipAddress;
        $_SERVER['REMOTE_ADDR'] = 'dont use this, this time';
        $output = $dataBuilder->makeData(Level::ERROR, "testing", array());
        $this->assertEquals($expected, $output->getRequest()->getUserIp());
        
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['HTTP_X_REAL_IP']);
    }
    
    public function getUserIpProvider()
    {
        return array(
            array('127.0.0.1', '127.0.0.1', null),
            array('127.0.0.1', null, false),
            array('127.0.0.1', '127.0.0.0', DataBuilder::ANONYMIZE_IP),
            array(
                '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
                '2001:0db8:85a3:0000:0000:0000:0000:0000',
                DataBuilder::ANONYMIZE_IP
            ),
            array(
                '2001:db8:85a3::',
                '2001:db8:85a3:0000:0000:0000:0000:0000',
                DataBuilder::ANONYMIZE_IP
            )
        );
    }

    public function testGitBranch()
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => 'tests'
        ));
        
        $dataBuilder = $config->getDataBuilder();
        
        $val = rtrim(shell_exec('git rev-parse --abbrev-ref HEAD'));
        $this->assertEquals($val, $dataBuilder->detectGitBranch());
    }

    public function testGitBranchNoExec()
    {
        $config = new Config(array(
            'access_token' => $this->getTestAccessToken(),
            'environment' => 'tests'
        ));
        
        $dataBuilder = $config->getDataBuilder();
        
        $this->assertEquals(null, $dataBuilder->detectGitBranch(false));
    }
}
