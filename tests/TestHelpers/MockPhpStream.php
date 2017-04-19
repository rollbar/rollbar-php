<?php

namespace Rollbar\TestHelpers;

class MockPhpStream
{
    
    protected $index = 0;
    protected $length = null;
    protected $data = '';
    
    public $context;
    
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
        if(is_null($this->length) === true){
            $this->length = strlen($this->data);
        }
        $length = min($count, $this->length - $this->index);
        $data = substr($this->data, $this->index);
        $this->index = $this->index + $length;
        return $data;
    }
    
    public function stream_eof()
    {
        return ($this->index >= $this->length ? true : false);
    }
    
    public function stream_write($data)
    {
        $this->data .= $data;
        $this->length = strlen($data);
        return $this->length;
    }
}
