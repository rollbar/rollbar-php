<?php namespace Rollbar\TestHelpers;

use Rollbar\Truncation\Truncation;
use Rollbar\Payload\EncodedPayload;

class TruncationPerformance extends Truncation
{
    protected $memoryUsage = 0;
    protected $timeUsage = 0;
    protected $payloadSize = 0;
    protected $lastRunOutput = "";
    protected $strategiesUsed = array();
    
    public function truncate(EncodedPayload $payload)
    {
        $this->strategiesUsed = array();
        
        $this->payloadSize = $payload->size();
        
        $memUsageBefore = memory_get_usage(true);
        $timeBefore = microtime(true) * 1000;
        
        EncodedPayload::ResetEncodingCount();
        
        $result = parent::truncate($payload);
        
        $timeAfter = microtime(true) * 1000;
        $memUsageAfter = memory_get_usage(true);
        
        $this->memoryUsage = $memUsageAfter - $memUsageBefore;
        $this->timeUsage = $timeAfter - $timeBefore;
        $this->strategiesUsed = array_unique($this->strategiesUsed);
        
        $this->lastRunOutput = $this->composeLastRunOutput();
        
        return $result;
    }
    
    public function needsTruncating(EncodedPayload $payload, $strategy)
    {
        $result = parent::needsTruncating($payload, $strategy);
        
        if ($result) {
            $this->strategiesUsed []= is_string($strategy) ? $strategy : get_class($strategy);
        }
        
        return $result;
    }
    
    public function getLastRun()
    {
        return $this->lastRunOutput;
    }
    
    public function composeLastRunOutput()
    {
        $output = "\n";
        
        $output .= "Payload size: " . $this->payloadSize . " bytes = " . round($this->payloadSize / 1024 / 1024, 2) . " MB \n";
        $output .= "Strategies used: " . join(", ", $this->strategiesUsed) . "\n";
        $output .= "Encoding triggered: " . EncodedPayload::GetEncodingCount() . "\n";
        $output .= "Memory usage: " . $this->memoryUsage . " bytes = " . round($this->memoryUsage / 1024 / 1024, 2) . " MB\n";
        $output .= "Execution time: " . $this->timeUsage . " ms\n";
        
        return $output;
    }
}
