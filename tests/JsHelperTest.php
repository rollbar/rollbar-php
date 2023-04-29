<?php namespace Rollbar;

class JsHelperTest extends BaseRollbarTest
{
    protected static RollbarJsHelper $jsHelper;
    protected static string|false $testSnippetPath = "";
    
    public function setUp(): void
    {
        self::$jsHelper = new RollbarJsHelper(array());
        self::$testSnippetPath = realpath(__DIR__ . "/../data/rollbar.snippet.js");
    }

    # TODO this is needed for phpUnit 10 as all Generators needs to be static
    public static function init(): void
    {
        self::$jsHelper = new RollbarJsHelper(array());
        self::$testSnippetPath = realpath(__DIR__ . "/../data/rollbar.snippet.js");
    }

    public function testSnippetPath(): void
    {
        $this->assertEquals(
            self::$testSnippetPath,
            self::$jsHelper->snippetPath()
        );
    }
    
    /**
     * @dataProvider shouldAddJsProvider
     */
    public function testShouldAddJs($setup, $expected): void
    {
        $mock = \Mockery::mock(\Rollbar\RollbarJsHelper::class);
             
        $status = $setup['status'];
        
        $mock->shouldReceive('isHtml')
             ->andReturn($setup['isHtml']);
             
        $mock->shouldReceive('hasAttachment')
             ->andReturn($setup['hasAttachment']);
             
        $mock->shouldReceive('shouldAddJs')
             ->passthru();
        
        $this->assertEquals($expected, $mock->shouldAddJs($status, array()));
    }
    
    public static function shouldAddJsProvider(): array
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
    public function testIsHtml($headers, $expected): void
    {
        $this->assertEquals(
            $expected,
            self::$jsHelper->isHtml($headers)
        );
    }
    
    public static function isHtmlProvider(): array
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
    public function testHasAttachment($headers, $expected): void
    {
        $this->assertEquals(
            $expected,
            self::$jsHelper->hasAttachment($headers)
        );
    }
    
    public static function hasAttachmentProvider(): array
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
    
    public function testJsSnippet(): void
    {
        $expected = file_get_contents(self::$testSnippetPath);
        
        $this->assertEquals($expected, self::$jsHelper->jsSnippet());
    }
    
    /**
     * @dataProvider shouldAppendNonceProvider
     */
    public function testShouldAppendNonce($headers, $expected): void
    {
        $this->assertEquals(
            $expected,
            self::$jsHelper->shouldAppendNonce($headers)
        );
    }
    
    public static function shouldAppendNonceProvider(): array
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
    public function testScriptTag($content, $headers, $nonce, $expected): void
    {
        if ($expected === 'Exception') {
            try {
                $result = self::$jsHelper->scriptTag($content, $headers, $nonce);
                
                $this->fail();
            } catch (\Exception $e) {
                $this->assertTrue(true);
                return;
            }
        } else {
            $result = self::$jsHelper->scriptTag($content, $headers, $nonce);
            
            $this->assertEquals($expected, $result);
        }
    }
    
    public static function scriptTagProvider(): array
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
    
    /**
     * @dataProvider configJsTagProvider
     */
    public function testConfigJsTag($config, $expectedJson): void
    {
        $expected = "var _rollbarConfig = $expectedJson;";
        
        $helper = new RollbarJsHelper($config);
        $result = $helper->configJsTag();
        
        $this->assertEquals($expected, $result);
    }
    
    public static function configJsTagProvider(): array
    {
        return array(
            array(array(), '{}'),
            array(array('config1' => 'value 1'), '{"config1":"value 1"}'),
            array(
                array('hostBlackList' => array('example.com', 'badhost.com')),
                '{"hostBlackList":["example.com","badhost.com"]}'
            ),
        );
    }

    /**
     * @dataProvider addJsProvider
     */
    public static function testBuildJs($config, $headers, $nonce, $expected): void
    {
        $result = RollbarJsHelper::buildJs(
            $config,
            $headers,
            $nonce,
            "var customJs = true;"
        );
        
        self::assertEquals($expected, $result);
    }
    
    /**
     * @dataProvider addJsProvider
     */
    public function testAddJs($config, $headers, $nonce, $expected): void
    {
        $helper = new RollbarJsHelper($config);
        
        $result = $helper->addJs(
            $headers,
            $nonce,
            "var customJs = true;"
        );
        
        $this->assertEquals($expected, $result);
    }

    # TODO this is needed for phpUnit 10 as all Generators needs to be static
    public static function addJsProvider(): array
    {
        self::init();
        $expectedJs = file_get_contents(self::$testSnippetPath);

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
