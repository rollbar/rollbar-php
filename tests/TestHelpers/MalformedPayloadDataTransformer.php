<?php namespace Rollbar\TestHelpers;

use Rollbar\Payload\Level;
use Rollbar\Payload\Payload;
use Rollbar\Payload\Data;
use Rollbar\TransformerInterface;

class MalformedPayloadDataTransformer implements TransformerInterface
{
    public function transform(
        Payload $payload,
        Level|string $level,
        mixed $toLog,
        array $context = array()
    ): Payload {
        $mock = \Mockery::mock(Data::class)->makePartial();
        $mock->shouldReceive("serialize")->andReturn(array());
        $mock->setLevel(\Rollbar\LevelFactory::fromName($level));
        $payload->setData($mock);
        return $payload;
    }
}
