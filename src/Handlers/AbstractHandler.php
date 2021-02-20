<?php declare(strict_types=1);

namespace Rollbar\Handlers;

use Rollbar\Rollbar;
use Rollbar\RollbarLogger;
use Rollbar\Payload\Level;

abstract class AbstractHandler
{
    protected $registered = false;
    
    protected $previousHandler = null;
    
    public function __construct(
        protected RollbarLogger $logger
    ) {
    }
    
    public function logger()
    {
        return $this->logger;
    }
    
    public function registered()
    {
        return $this->registered;
    }
    
    public function handle(...$args)
    {
        if (!$this->registered()) {
            throw new \Exception(get_class($this) . ' has not been set up.');
        }
    }
    
    public function register()
    {
        $this->registered = true;
    }
}
