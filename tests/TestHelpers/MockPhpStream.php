<?php

namespace Rollbar\TestHelpers;

class MockPhpStream
{
    
    protected $index = 0;
    protected $length = null;
    /**
     * @var $data Data written to the stream. This needs to be static as
     * there are multiple instances of MockPhpStream being used in PHP
     * to deal with the stream wrapper.
     */
    protected static $data = '';
    
    // @codingStandardsIgnoreStart
    
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        return true;
    }
    
    public function stream_stat()
    {
        return array();
    }
    
    public function stream_read($count)
    {
        if (is_null($this->length) === true) {
            $this->length = strlen(self::$data);
        }
        $length = min($count, $this->length - $this->index);
        $data = substr(self::$data, $this->index);
        $this->index = $this->index + $length;
        return $data;
    }
    
    public function stream_eof()
    {
        return ($this->index >= $this->length ? true : false);
    }
    
    public function stream_write($data)
    {
        self::$data .= $data;
        $this->length = strlen(self::$data);
        return $this->length;
    }
    
    // @codingStandardsIgnoreEnd
}
