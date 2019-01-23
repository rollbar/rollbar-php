<?php

namespace Rollbar;

use Rollbar\Payload\Level;
use Rollbar\TestHelpers\MockPhpStream;

class ScrubberTest extends BaseRollbarTest
{
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
     * @dataProvider scrubWhitelistProvider
     */
    public function testScrubWhitelist($testData, $scrubFields, $whitelist, $expected)
    {
        $scrubber = new Scrubber(array(
            'scrubFields' => $scrubFields,
            'scrubWhitelist' => $whitelist
        ));
        $result = $scrubber->scrub($testData);
        $this->assertEquals(
            $expected,
            $result,
            "Looks like whitelisting is not working correctly."
        );
    }
    
    public function scrubWhitelistProvider()
    {
        return array(
            array(
                array(
                    'toScrub' => 'some value 1',
                    'firstLevelArray' => array(
                        'secondLevelArray' => array(
                            'thirdLevelProp' => 'some value 3',
                            'toScrub' => 'some value 3',
                            'thirdLevelArray' => array(
                                'toScrub' => 'some value 4'
                            )
                        ),
                        'secondLevelProp' => 'some value 2',
                        'toScrub' => 'some value 2'
                    )
                ),
                
                array('toScrub'),
                
                array(
                    'firstLevelArray.secondLevelArray.toScrub',
                    'firstLevelArray.secondLevelArray.thirdLevelArray.toScrub'
                ),
                
                array(
                    'toScrub' => '********',
                    'firstLevelArray' => array(
                        'secondLevelArray' => array(
                            'thirdLevelProp' => 'some value 3',
                            'toScrub' => 'some value 3',
                            'thirdLevelArray' => array(
                                'toScrub' => 'some value 4'
                            )
                        ),
                        'secondLevelProp' => 'some value 2',
                        'toScrub' => '********'
                    )
                ),
            )
        );
    }
    
    /**
     * @dataProvider scrubDataProvider
     */
    public function testScrub($testData, $scrubFields, $expected)
    {
        $scrubber = new Scrubber(array(
            'scrubFields' => $scrubFields
        ));
        $result = $scrubber->scrub($testData);
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
                'non sensitive data 7' => 'a=stuff&foo=superSecret',
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
                'non sensitive data 7' => 'a=stuff&foo=xxxxxxxx',
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
                                'sensitive' => 'scrubit',
                                'SENSITIVE' => 'scrubit',
                                'sensitive2' => 'scrubit'
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
                'sensitive',
                'SENSITIVE2'
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
                                'sensitive' => 'xxxxxxxx',
                                'SENSITIVE' => 'xxxxxxxx',
                                'sensitive2' => 'xxxxxxxx'
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
        $scrubber = new Scrubber(array(
            'scrubFields' => $scrubFields
        ));
        $result = $scrubber->scrub($testData);
        $this->assertEquals($expected, $result, "Looks like some fields did not get scrubbed correctly.");
    }

    public function scrubArrayDataProvider()
    {
        return array(
            'flat data array' => array(
                array( // $testData
                    'non sensitive data' => '123',
                    'sensitive data' => '456',
                    'UPPERCASE SENSITIVE DATA' => '789',
                    'also sensitive data' => '012'
                ),
                array( // $scrubFields
                    'sensitive data',
                    'uppercase sensitive data',
                    'ALSO SENSITIVE DATA'
                ),
                array( // $expected
                    'non sensitive data' => '123',
                    'sensitive data' => '********',
                    'UPPERCASE SENSITIVE DATA' => '********',
                    'also sensitive data' => '********'
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
        
        $scrubber = new Scrubber(array(
            'scrubFields' => array('scrubit')
        ));
        
        $result = $scrubber->scrub($testData, "@@@@@@@@");

        $this->assertEquals("@@@@@@@@", $result['scrubit']);
    }
}
