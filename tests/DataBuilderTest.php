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
        // TODO: test for scrub data in makeData()
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

    // TODO: changing DataBuilderTest.php code breaks this test.
    // This is not ideal. Should be done in a different way so
    // development of DataBuilderTest.php doesn't break the
    // this test.
    public function testFramesWithContext()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'include_error_code_context' => true
        ));
        $backTrace = array(
            array(
                'file' => __DIR__ . '/DataBuilderTest.php',
                'line' => 68,
                'function' => 'testFramesWithoutContext'
            ),
            array(
                'file' => __DIR__ . '/DataBuilderTest.php',
                'line' => 79,
                'function' => 'testFramesWithContext'
            ),
        );
        $output = $dataBuilder->makeFrames(new ErrorWrapper(null, null, null, null, $backTrace));
        $pre = $output[0]->getContext()->getPre();
        $expected = array(
            '            \'host\' => \'my host\'',
            '        ));',
            '        $output = $dataBuilder->makeData(Level::fromName(\'error\'), "testing", array());',
            '        $this->assertEquals(\'my host\', $output->getServer()->getHost());',
            '    }',
            ''
        );
        $this->assertEquals($expected, $pre);
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

    public function testMakeDataScrub()
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'scrub_fields' => array('test')
        ));
        $_POST['test'] = 'blah';
        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", array());
        $post = $output->getRequest()->getPost();
        $this->assertEquals('********', $post['test']);
    }

    public function testGetRequestScrubGET()
    {
        $_GET['Secret data'] = 'Secret value';

        $scrubFields = array('Secret data');

        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'scrub_fields' => $scrubFields
        ));

        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", array());

        $result = $output->getRequest()->getGet();
        $this->assertEquals('********', $result['Secret data'], "GET arguments of the request did not get scrubbed.");
    }

    public function testGetRequestScrubQueryString()
    {
        $_SERVER['QUERY_STRING'] = '?arg1=val1&arg2=val2&arg3=val3';

        $scrubFields = array('arg2');

        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'scrub_fields' => $scrubFields
        ));

        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", array());

        // Test scrubbing query string
        $result = $output->getRequest()->getQueryString();
        $this->assertEquals(
            '?arg1=val1&arg2=xxxxxxxx&arg3=val3', 
            $result, '$_SERVER[\'QUERY_STRING\'] did not get scrubbed.');
    }

    public function testGetRequestScrubPOST()
    {
        $_POST = array(
            'non-sensitive' => 'value 1',
            'sensitive' => 'value 2',
            'recursive' => array(
                'sensitive' => 'value 1',
                'non-sensitive' => 'value 2'
            )
        );

        $scrubFields = array('sensitive');

        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'scrub_fields' => $scrubFields
        ));

        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", array());

        $result = $output->getRequest()->getPost();

        $this->assertEquals(
            '********', 
            $result['sensitive'], 
            '$_POST did not get scrubbed.');

        $this->assertEquals(
            '********', 
            $result['recursive']['sensitive'], 
            '$_POST did not get scrubbed recursively.');
    }

    public function testGetRequestScrubExtras()
    {
        $extras = array(
            'extraField1' => array(
                'non-sensitive' => 'value 1',
                'sensitive' => 'value 2',
                'recursive' => array(
                    'sensitive' => 'value 1',
                    'non-sensitive' => 'value 2'
                )
            )
        );

        $scrubFields = array('sensitive');

        // TODO: need to check with Andrew if this
        // is a reliable way of passing the extras

        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'scrub_fields' => $scrubFields,
            'requestExtras' => $extras
        ));

        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", array());

        $result = $output->getRequest()->extraField1;

        $this->assertEquals(
            '********', 
            $result['sensitive'], 
            '$_POST did not get scrubbed.');

        $this->assertEquals(
            '********', 
            $result['recursive']['sensitive'], 
            '$_POST did not get scrubbed recursively.');
    }

    public function testGetRequestScrubBodyContext()
    {
        $bodyContext = array(
            'context1' => array(
                'non-sensitive' => 'value 1',
                'sensitive' => 'value 2',
                'recursive' => array(
                    'sensitive' => 'value 1',
                    'non-sensitive' => 'value 2'
                )
            )
        );

        $scrubFields = array('sensitive');

        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'scrub_fields' => $scrubFields
        ));

        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", $bodyContext);

        $result = $output->getBody()->getValue()->context1;

        $this->assertEquals(
            '********', 
            $result['sensitive'], 
            '$_POST did not get scrubbed.');

        $this->assertEquals(
            '********', 
            $result['recursive']['sensitive'], 
            '$_POST did not get scrubbed recursively.');
    }

    public function testGetRequestScrubSession()
    {
        $_SESSION = array(
            'non-sensitive' => 'value 1',
            'sensitive' => 'value 2',
            'recursive' => array(
                'sensitive' => 'value 1',
                'non-sensitive' => 'value 2'
            )
        );

        $scrubFields = array('sensitive');

        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'scrub_fields' => $scrubFields
        ));

        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", array());

        $result = $output->getRequest()->session;

        $this->assertEquals(
            '********', 
            $result['sensitive'], 
            '$_POST did not get scrubbed.');

        $this->assertEquals(
            '********', 
            $result['recursive']['sensitive'], 
            '$_POST did not get scrubbed recursively.');
    }

    public function testGetScrubbedHeaders()
    {
        $_SERVER['HTTP_CONTENT_TYPE'] = 'text/html; charset=utf-8';
        $_SERVER['HTTP_SECRET_DATA'] = 'Secret value';

        $scrubFields = array('Secret-Data');

        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'scrub_fields' => $scrubFields
        ));

        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", array());

        $result = $output->getRequest()->getHeaders($scrubFields);

        $this->assertEquals('********', $result['Secret-Data']);        
    }

    public function testGetUrlScrub()
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/index.php?arg1=val1&arg2=val2&arg3=val3';

        $scrubFields = array('arg2');

        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests',
            'scrub_fields' => $scrubFields
        ));

        $output = $dataBuilder->makeData(Level::fromName('error'), "testing", array());

        $result = $output->getRequest()->getUrl();

        $this->assertEquals(
            'http://localhost/index.php?arg1=val1&arg2=xxxxxxxx&arg3=val3',
            $result);
    }

    /**
     * @dataProvider scrubUrlDataProvider
     */
    public function testScrubUrl($testData, $scrubFields, $expected)
    {
        $result = DataBuilder::scrubUrl($testData, $scrubFields);
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
            )
        );
    }

    public function testScrubReplacement()
    {
        $result = DataBuilder::scrub(
            array('scrubit' => '123'),
            array('scrubit'),
            "@"
        );

        $this->assertEquals("@@@@@@@@", $result['scrubit']);
    }
}
