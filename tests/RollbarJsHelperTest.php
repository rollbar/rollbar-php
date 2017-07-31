<?php namespace Rollbar;

class JsHelperTest extends BaseRollbarTest
{
    protected $jsHelper;
    protected $testSnippetPath;
    
    public function setUp()
    {
        $this->jsHelper = new RollbarJsHelper(array());
        $this->testSnippetPath = realpath(__DIR__ . "/../data/rollbar.snippet.js");
    }
    
    public function testSnippetPath()
    {
        $this->assertEquals(
            $this->testSnippetPath,
            $this->jsHelper->snippetPath()
        );
    }
    
    /**
     * @dataProvider shouldAddJsProvider
     */
    public function testShouldAddJs($setup, $expected)
    {
        $mock = \Mockery::mock('Rollbar\RollbarJsHelper');
             
        $status = $setup['status'];
        
        $mock->shouldReceive('isHtml')
             ->andReturn($setup['isHtml']);
             
        $mock->shouldReceive('hasAttachment')
             ->andReturn($setup['hasAttachment']);
             
        $mock->shouldReceive('shouldAddJs')
             ->passthru();
        
        $this->assertEquals($expected, $mock->shouldAddJs($status, array()));
    }
    
    public function shouldAddJsProvider()
    {
        return array(
            array(
                array(
                    'status' => 200,
                    'isHtml' => true,
                    'hasAttachment' => false
                ),
                true
            ),
            array(
                array(
                    'status' => 500,
                    'isHtml' => true,
                    'hasAttachment' => false
                ),
                false
            ),
            array(
                array(
                    'status' => 200,
                    'isHtml' => false,
                    'hasAttachment' => false
                ),
                false
            ),
            array(
                array(
                    'status' => 200,
                    'isHtml' => true,
                    'hasAttachment' => true
                ),
                false
            ),
        );
    }
    
    /**
     * @dataProvider isHtmlProvider
     */
    public function testIsHtml($headers, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->jsHelper->isHtml($headers)
        );
    }
    
    public function isHtmlProvider()
    {
        return array(
            array(
                array(
                    'Content-Type: text/html'
                ),
                true
            ),
            array(
                array(
                    'Content-Type: text/plain'
                ),
                false
            ),
        );
    }
    
    /**
     * @dataProvider hasAttachmentProvider
     */
    public function testHasAttachment($headers, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->jsHelper->hasAttachment($headers)
        );
    }
    
    public function hasAttachmentProvider()
    {
        return array(
            array(
                array(
                    'Content-Disposition: attachment'
                ),
                true
            ),
            array(
                array(
                ),
                false
            ),
        );
    }
    
    public function testJsSnippet()
    {
        $expected = file_get_contents($this->testSnippetPath);
        
        $this->assertEquals($expected, $this->jsHelper->jsSnippet());
    }
    
    /**
     * @dataProvider shouldAppendNonceProvider
     */
    public function testShouldAppendNonce($headers, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->jsHelper->shouldAppendNonce($headers)
        );
    }
    
    public function shouldAppendNonceProvider()
    {
        return array(
            array(
                array(
                    "Content-Security-Policy: script-src 'unsafe-inline'"
                ),
                true
            ),
            array(
                array(
                    "Content-Type: text/html"
                ),
                false
            ),
            array(
                array(
                    "Content-Security-Policy: default-src 'self'"
                ),
                false
            ),
        );
    }
    
    /**
     * @dataProvider scriptTagProvider
     */
    public function testScriptTag($content, $headers, $nonce, $expected)
    {
        if ($expected === 'Exception') {
            try {
                $result = $this->jsHelper->scriptTag($content, $headers, $nonce);
                
                $this->fail();
            } catch (\Exception $e) {
                $this->assertTrue(true);
                return;
            }
        } else {
            $result = $this->jsHelper->scriptTag($content, $headers, $nonce);
            
            $this->assertEquals($expected, $result);
        }
    }
    
    public function scriptTagProvider()
    {
        return array(
            'nonce script' => array(
                'var test = "value 1";',
                array(
                    "Content-Security-Policy: script-src 'unsafe-inline'"
                ),
                '123',
                "\n<script type=\"text/javascript\" nonce=\"123\">var test = \"value 1\";</script>"
            ),
            'script-src inline-unsafe throws Exception' => array(
                'var test = "value 1";',
                array(
                    "Content-Security-Policy: script-src 'inline-unsafe'"
                ),
                null,
                'Exception'
            ),
            array(
                'var test = "value 1";',
                array(),
                null,
                "\n<script type=\"text/javascript\">var test = \"value 1\";</script>"
            ),
        );
    }
    
    public function testConfigJsTag()
    {
        $config = array(
            'config1' => 'value 1'
        );
        
        $expectedJson = json_encode($config);
        $expected = "var _rollbarConfig = $expectedJson;";
        
        $helper = new RollbarJsHelper($config);
        $result = $helper->configJsTag();
        
        $this->assertEquals($expected, $result);
    }
    
    /**
     * @dataProvider addJsProvider
     */
    public function testBuildJs($config, $headers, $nonce, $expected)
    {
        $result = RollbarJsHelper::buildJs(
            $config,
            $headers,
            $nonce,
            "var customJs = true;"
        );
        
        $this->assertEquals($expected, $result);
    }
    
    /**
     * @dataProvider addJsProvider
     */
    public function testAddJs($config, $headers, $nonce, $expected)
    {
        $helper = new RollbarJsHelper($config);
        
        $result = $helper->addJs(
            $headers,
            $nonce,
            "var customJs = true;"
        );
        
        $this->assertEquals($expected, $result);
    }
    
    public function addJsProvider()
    {
        $this->setUp();
        $expectedJs = file_get_contents($this->testSnippetPath);
        return array(
            array(
                array(), // 'config'
                array(), // 'headers'
                null,   // 'nonce'
                "\n<script type=\"text/javascript\">" .
                "var _rollbarConfig = {};" .
                $expectedJs . ";" .
                "var customJs = true;" .
                "</script>"
            ),
            array(
                array(
                    'foo' => 'bar'
                ),
                array(),
                null,
                "\n<script type=\"text/javascript\">" .
                "var _rollbarConfig = {\"foo\":\"bar\"};" .
                $expectedJs . ";" .
                "var customJs = true;" .
                "</script>"
            ),
            array(
                array(),
                array(
                    'Content-Security-Policy: script-src \'unsafe-inline\''
                ),
                'stub-nonce',
                "\n<script type=\"text/javascript\" nonce=\"stub-nonce\">" .
                "var _rollbarConfig = {};" .
                $expectedJs . ";" .
                "var customJs = true;" .
                "</script>"
            ),
        );
    }
}
