<?php

namespace Rollbar;

interface iRollbarLogger {
    public function log($level, $msg);
}

