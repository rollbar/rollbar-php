<?php

namespace Rollbar\Truncation;

use Rollbar\Payload\EncodedPayload;
use \Rollbar\Config;
use \Rollbar\BaseRollbarTest;

class FramesStrategyTest extends BaseRollbarTest
{
    /**
     * @dataProvider executeProvider
     */
    public function testExecute($data, $expected)
    {
        $config = new Config(array('access_token' => $this->getTestAccessToken()));
        $truncation = new Truncation($config);
                    
        $strategy = new FramesStrategy($truncation);
        
        $data = new EncodedPayload($data);
        $data->encode();
        
        $result = $strategy->execute($data);
        
        $this->assertEquals($expected, $result->data());
    }
    
    public function executeProvider()
    {
        $data = array(
            'nothing to truncate using trace key' => array(
                array('data' => array('body' =>
                    array('trace' => array('frames' => range(1, 6)))
                )),
                array('data' => array('body' =>
                    array('trace' => array('frames' => range(1, 6)))
                ))
            ),
            'nothing to truncate using trace_chain key' => array(
                array('data' => array('body' =>
                    array('trace_chain' => array(array('frames' => range(1, 6))))
                )),
                array('data' => array('body' =>
                    array('trace_chain' => array(array('frames' => range(1, 6))))
                ))
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
                array('data' => array('body' =>
                    array(
                        'trace' => array(
                            'frames' => array_merge(
                                range(1, FramesStrategy::FRAMES_OPTIMIZATION_RANGE),
                                range(
                                    FramesStrategy::FRAMES_OPTIMIZATION_RANGE + 2,
                                    FramesStrategy::FRAMES_OPTIMIZATION_RANGE * 2 + 1
                                )
                            )
                        )
                    )
                ))
                
            ),
            'truncate middle using trace_chain key' => array(
                array('data' => array('body' =>
                    array(
                        'trace_chain' => array(
                            array(
                                'frames' => range(
                                    1,
                                    FramesStrategy::FRAMES_OPTIMIZATION_RANGE * 2 + 1
                                )
                            )
                        )
                    )
                )),
                array('data' => array('body' =>
                    array(
                        'trace_chain' => array(
                            array(
                                'frames' => array_merge(
                                    range(1, FramesStrategy::FRAMES_OPTIMIZATION_RANGE),
                                    range(
                                        FramesStrategy::FRAMES_OPTIMIZATION_RANGE + 2,
                                        FramesStrategy::FRAMES_OPTIMIZATION_RANGE * 2 + 1
                                    )
                                )
                            )
                        )
                    )
                ))
                
            )
        );
        
        return $data;
    }
}
