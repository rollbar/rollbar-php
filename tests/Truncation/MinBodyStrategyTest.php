<?php

namespace Rollbar\Truncation;

use Rollbar\Payload\EncodedPayload;
use Rollbar\Config;
use Rollbar\BaseRollbarTest;

class MinBodyStrategyTest extends BaseRollbarTest
{
    
    /**
     * @dataProvider executeProvider
     */
    public function testExecute($data, $expected): void
    {
        $config = new Config(array('access_token' => $this->getTestAccessToken()));
        $truncation = new Truncation($config);

        $strategy = new MinBodyStrategy($truncation);
        
        $data = new EncodedPayload($data);
        $data->encode();
        
        $result = $strategy->execute($data);
        
        $this->assertEquals($expected, $result->data());
    }
    
    public static function executeProvider(): array
    {
        $data = array();
        
        $traceData = array(
            'exception' => array(
                'description' => 'Test description',
                'message' => str_repeat(
                    'A',
                    MinBodyStrategy::EXCEPTION_MESSAGE_LIMIT+1
                )
            ),
            'frames' => array('Frame 1', 'Frame 2', 'Frame 3')
        );
        
        $expected = $traceData;
        unset($expected['exception']['description']);
        $expected['exception']['message'] = str_repeat(
            'A',
            MinBodyStrategy::EXCEPTION_MESSAGE_LIMIT
        );
        $expected['frames'] = array('Frame 1', 'Frame 3');
        
        
        $data['trace data set'] = array(
            self::payloadStructureProvider(array('trace' => $traceData)),
            self::payloadStructureProvider(array('trace' => $expected))
        );
        
        $data['trace_chain data set'] = array(
            self::payloadStructureProvider(array('trace_chain' => $traceData)),
            self::payloadStructureProvider(array('trace_chain' => $expected))
        );
        return $data;
    }
    
    protected static function payloadStructureProvider($body): array
    {
        return array(
            "data" => array(
                "body" => $body
            )
        );
    }
}
