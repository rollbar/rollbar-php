<?php

namespace Rollbar\Truncation;

use Rollbar\DataBuilder;

class FramesStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider executeProvider
     */
    public function testExecute($data, $expected)
    {
        $dataBuilder = new DataBuilder(array(
            'accessToken' => 'abcd1234efef5678abcd1234567890be',
            'environment' => 'tests'
        ));
                    
        $strategy = new FramesStrategy($dataBuilder);
        $result = $strategy->execute($data);
        
        $this->assertEquals($expected, $result);
    }
    
    public function executeProvider()
    {
        $data = array(
            'nothing to truncate using trace key' => array(
                array('data' => array('body' =>
                    array('trace' => array('frames' => range(1, 6)))
                )),
                range(1, 6)
            ),
            'nothing to truncate using trace_chain key' => array(
                array('data' => array('body' =>
                    array('trace_chain' => array('frames' => range(1, 6)))
                )),
                range(1, 6)
            ),
            'truncate middle using trace key' => array(
                array('data' => array('body' =>
                    array(
                        'trace' => array(
                            'frames' => range(
                                1,
                                FramesStrategy::FRAMES_OPTIMIZATION_RANGE * 2 + 1
                            )
                        )
                    )
                )),
                array_merge(
                    range(1, FramesStrategy::FRAMES_OPTIMIZATION_RANGE),
                    range(
                        FramesStrategy::FRAMES_OPTIMIZATION_RANGE + 2,
                        FramesStrategy::FRAMES_OPTIMIZATION_RANGE * 2 + 1
                    )
                )
            ),
            'truncate middle using trace_chain key' => array(
                array('data' => array('body' =>
                    array(
                        'trace_chain' => array(
                            'frames' => range(
                                1,
                                FramesStrategy::FRAMES_OPTIMIZATION_RANGE * 2 + 1
                            )
                        )
                    )
                )),
                array_merge(
                    range(1, FramesStrategy::FRAMES_OPTIMIZATION_RANGE),
                    range(
                        FramesStrategy::FRAMES_OPTIMIZATION_RANGE + 2,
                        FramesStrategy::FRAMES_OPTIMIZATION_RANGE * 2 + 1
                    )
                )
            )
        );
        
        return $data;
    }
}
