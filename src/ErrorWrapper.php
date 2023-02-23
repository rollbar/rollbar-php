<?php declare(strict_types=1);

namespace Rollbar;

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
        return self::$constName[$const] ?? null;
    }

    /**
     * Creates the instance from the error data.
     *
     * @param int         $errorLevel   The level of the error raised.
     * @param string      $errorMessage The error message.
     * @param string|null $errorFile    The filename that the error was raised in.
     * @param int|null    $errorLine    The line number where the error was raised.
     * @param array|null  $backTrace    The stack trace for the error.
     * @param Utilities   $utilities    The configured utilities class.
     */
    public function __construct(
        public int $errorLevel,
        public string $errorMessage,
        public ?string $errorFile,
        public ?int $errorLine,
        public ?array $backTrace,
        $utilities
    ) {
        parent::__construct($this->errorMessage, $this->errorLevel);
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
