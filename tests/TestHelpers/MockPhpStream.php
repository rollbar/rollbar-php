<?php

namespace Rollbar\TestHelpers;

class MockPhpStream
{
    public $context;
    protected static int $index = 0;
    protected static $length = null;

    /**
     * @var $data Data written to the stream. This needs to be static as
     * there are multiple instances of MockPhpStream being used in PHP
     * to deal with the stream wrapper.
     */
    protected static string|Data $data = '';
    
    // @codingStandardsIgnoreStart
    
    public function stream_open(): bool
    {
        return true;
    }
    
    public function stream_stat(): array
    {
        return array();
    }
    
    public function stream_read($count): string
    {
        if (is_null(self::$length) === true) {
            $this->length = strlen(self::$data);
        }
        $length = min($count, self::$length - self::$index);
        $data = substr(self::$data, self::$index);
        self::$index = self::$index + $length;
        return $data;
    }
    
    public function stream_eof(): bool
    {
        return (self::$index >= self::$length ? true : false);
    }
    
    public function stream_write($data): ?int
    {
        self::$data = $data;
        self::$length = strlen(self::$data);
        self::$index = 0;
        return self::$length;
    }
    
    // @codingStandardsIgnoreEnd
}
