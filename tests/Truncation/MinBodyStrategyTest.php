<?php

namespace Rollbar\Truncation;

use Rollbar\BaseUnitTestCase;
use Rollbar\DataBuilder;

class MinBodyStrategyTest extends BaseUnitTestCase
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

        $strategy = new MinBodyStrategy($dataBuilder);
        $result = $strategy->execute($data);

        $this->assertEquals($expected, $result);
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
