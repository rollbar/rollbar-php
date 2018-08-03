<?php

namespace Rollbar\Truncation;

use Rollbar\Payload\EncodedPayload;

class MinBodyStrategyTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * @dataProvider executeProvider
     */
    public function testExecute($data, $expected)
    {
        $truncation = new Truncation();

        $strategy = new MinBodyStrategy($truncation);
        
        $data = new EncodedPayload($data);
        $data->encode();
        
        $result = $strategy->execute($data);
        
        $this->assertEquals($expected, $result->data());
    }
    
    public function executeProvider()
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
            $this->payloadStructureProvider(array('trace' => $traceData)),
            $this->payloadStructureProvider(array('trace' => $expected))
        );
        
        $data['trace_chain data set'] = array(
            $this->payloadStructureProvider(array('trace_chain' => $traceData)),
            $this->payloadStructureProvider(array('trace_chain' => $expected))
        );
        return $data;
    }
    
    protected function payloadStructureProvider($body)
    {
        return array(
            "data" => array(
                "body" => $body
            )
        );
    }
}
