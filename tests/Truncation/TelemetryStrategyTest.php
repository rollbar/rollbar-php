<?php

namespace Rollbar\Truncation;

use Rollbar\BaseRollbarTest;
use Rollbar\Config;
use Rollbar\Payload\EncodedPayload;
use Rollbar\Rollbar;

class TelemetryStrategyTest extends BaseRollbarTest
{

    public function setUp(): void
    {
        Rollbar::init([
            'access_token' => $this->getTestAccessToken(),
            'environment' => 'test',
        ]);
    }

    /**
     * @dataProvider executeProvider
     */
    public function testExecute(array $data, array $expected): void
    {
        $config = new Config(['access_token' => $this->getTestAccessToken()]);
        $truncation = new Truncation($config);

        $strategy = new TelemetryStrategy($truncation);

        $data = new EncodedPayload($data);
        $data->encode();

        $result = $strategy->execute($data);

        $this->assertEquals($expected, $result->data());
    }

    /**
     * @return array
     */
    public static function executeProvider(): array
    {
        return [
            'nothing to truncate: no telemetry data' => [
                [
                    'data' => [
                        'body' => [],
                    ],
                ],
                [
                    'data' => [
                        'body' => [],
                    ],
                ],
            ],
            'nothing to truncate: telemetry in range' => [
                [
                    'data' => [
                        'body' => [
                            'telemetry' => range(1, 6),
                        ],
                    ],
                ],
                [
                    'data' => [
                        'body' => [
                            'telemetry' => range(1, 6),
                        ],
                    ],
                ],
            ],
            'truncate middle: telemetry too long' => [
                [
                    'data' => [
                        'body' => [
                            'telemetry' => range(1, TelemetryStrategy::TELEMETRY_OPTIMIZATION_RANGE * 2 + 1),
                        ],
                    ],
                ],
                [
                    'data' => [
                        'body' => [
                            'telemetry' => array_merge(
                                range(1, TelemetryStrategy::TELEMETRY_OPTIMIZATION_RANGE),
                                range(
                                    TelemetryStrategy::TELEMETRY_OPTIMIZATION_RANGE + 2,
                                    TelemetryStrategy::TELEMETRY_OPTIMIZATION_RANGE * 2 + 1
                                ),
                            ),
                        ],
                    ],
                ],
            ],
        ];
    }
}
