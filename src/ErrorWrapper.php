<?php namespace Rollbar;

class ErrorWrapper extends \Exception
{
    private static $constName;
    
    private $utilities;

    private static function getConstName($const)
    {
        if (is_null(self::$constName)) {
            self::$constName = array(
                E_ERROR => "E_ERROR",
                E_WARNING => "E_WARNING",
                E_PARSE => "E_PARSE",
                E_NOTICE => "E_NOTICE",
                E_CORE_ERROR => "E_CORE_ERROR",
                E_CORE_WARNING => "E_CORE_WARNING",
                E_COMPILE_ERROR => "E_COMPILE_ERROR",
                E_COMPILE_WARNING => "E_COMPILE_WARNING",
                E_USER_ERROR => "E_USER_ERROR",
                E_USER_WARNING => "E_USER_WARNING",
                E_USER_NOTICE => "E_USER_NOTICE",
                E_STRICT => "E_STRICT",
                E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
                E_DEPRECATED => "E_DEPRECATED",
                E_USER_DEPRECATED => "E_USER_DEPRECATED"
            );
        }
        return isset(self::$constName[$const]) ? self::$constName[$const] : null;
    }

    public $errorLevel;
    public $errorMessage;
    public $errorFile;
    public $errorLine;
    public $backTrace;

    public function __construct(
        $errorLevel,
        $errorMessage,
        $errorFile,
        $errorLine,
        $backTrace,
        $utilities
    ) {
    
        parent::__construct($errorMessage, $errorLevel);
        $this->errorLevel = $errorLevel;
        $this->errorMessage = $errorMessage;
        $this->errorFile = $errorFile;
        $this->errorLine = $errorLine;
        $this->backTrace = $backTrace;
        $this->utilities = $utilities;
    }

    public function getBacktrace()
    {
        return $this->backTrace;
    }

    public function getClassName()
    {
        $constName = self::getConstName($this->errorLevel) ?: "#$this->errorLevel";
        return "$constName";
    }
}
