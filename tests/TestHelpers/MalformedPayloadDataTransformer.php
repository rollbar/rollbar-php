<?php namespace Rollbar\TestHelpers;

use \Rollbar\Payload\Payload;

class MalformedPayloadDataTransformer implements \Rollbar\TransformerInterface
{
    public function transform(Payload $payload, $level, $toLog, $context)
    {
        $mock = \Mockery::mock('\Rollbar\Payload\Data')->makePartial();
        $mock->shouldReceive("serialize")->andReturn(false);
        $levelFactory = new \Rollbar\LevelFactory();
        $mock->setLevel($levelFactory->fromName($level));
        $payload->setData($mock);
        return $payload;
    }
}
