<?php

declare(strict_types = 1);

namespace Superclasses;

/**
 * String-backed enum mapping PHP error type constants to their string representations.
 */
enum ErrorType: string
{
    case ERROR = 'E_ERROR';
    case WARNING = 'E_WARNING';
    case PARSE = 'E_PARSE';
    case NOTICE = 'E_NOTICE';
    case CORE_ERROR = 'E_CORE_ERROR';
    case CORE_WARNING = 'E_CORE_WARNING';
    case COMPILE_ERROR = 'E_COMPILE_ERROR';
    case COMPILE_WARNING = 'E_COMPILE_WARNING';
    case USER_ERROR = 'E_USER_ERROR';
    case USER_WARNING = 'E_USER_WARNING';
    case USER_NOTICE = 'E_USER_NOTICE';
    case STRICT = 'E_STRICT';
    case RECOVERABLE_ERROR = 'E_RECOVERABLE_ERROR';
    case DEPRECATED = 'E_DEPRECATED';
    case USER_DEPRECATED = 'E_USER_DEPRECATED';

    /**
     * Get the corresponding PHP error constant value.
     */
    public function toInt(): int
    {
        return match ($this) {
            self::ERROR => E_ERROR,
            self::WARNING => E_WARNING,
            self::PARSE => E_PARSE,
            self::NOTICE => E_NOTICE,
            self::CORE_ERROR => E_CORE_ERROR,
            self::CORE_WARNING => E_CORE_WARNING,
            self::COMPILE_ERROR => E_COMPILE_ERROR,
            self::COMPILE_WARNING => E_COMPILE_WARNING,
            self::USER_ERROR => E_USER_ERROR,
            self::USER_WARNING => E_USER_WARNING,
            self::USER_NOTICE => E_USER_NOTICE,
            self::STRICT => E_STRICT,
            self::RECOVERABLE_ERROR => E_RECOVERABLE_ERROR,
            self::DEPRECATED => E_DEPRECATED,
            self::USER_DEPRECATED => E_USER_DEPRECATED,
        };
    }

    /**
     * Get a human-readable description of the error level.
     */
    public function description(): string
    {
        return match ($this) {
            self::ERROR => 'Fatal run-time error',
            self::WARNING => 'Run-time warning (non-fatal)',
            self::PARSE => 'Compile-time parse error',
            self::NOTICE => 'Run-time notice',
            self::CORE_ERROR => 'Fatal error during PHP startup',
            self::CORE_WARNING => 'Warning during PHP startup',
            self::COMPILE_ERROR => 'Fatal compile-time error',
            self::COMPILE_WARNING => 'Compile-time warning',
            self::USER_ERROR => 'User-generated fatal error',
            self::USER_WARNING => 'User-generated warning',
            self::USER_NOTICE => 'User-generated notice',
            self::STRICT => 'Strict standards notice',
            self::RECOVERABLE_ERROR => 'Catchable fatal error',
            self::DEPRECATED => 'Deprecated feature notice',
            self::USER_DEPRECATED => 'User-generated deprecated notice',
        };
    }

    /**
     * Create an ErrorLevel from an integer error type.
     */
    public static function fromInt(int $error_type): ?self
    {
        return match ($error_type) {
            E_ERROR => self::ERROR,
            E_WARNING => self::WARNING,
            E_PARSE => self::PARSE,
            E_NOTICE => self::NOTICE,
            E_CORE_ERROR => self::CORE_ERROR,
            E_CORE_WARNING => self::CORE_WARNING,
            E_COMPILE_ERROR => self::COMPILE_ERROR,
            E_COMPILE_WARNING => self::COMPILE_WARNING,
            E_USER_ERROR => self::USER_ERROR,
            E_USER_WARNING => self::USER_WARNING,
            E_USER_NOTICE => self::USER_NOTICE,
            E_STRICT => self::STRICT,
            E_RECOVERABLE_ERROR => self::RECOVERABLE_ERROR,
            E_DEPRECATED => self::DEPRECATED,
            E_USER_DEPRECATED => self::USER_DEPRECATED,
            default => null,
        };
    }

    /**
     * Get all error levels that are considered fatal.
     */
    public static function fatalLevels(): array
    {
        return [
            self::ERROR,
            self::PARSE,
            self::CORE_ERROR,
            self::COMPILE_ERROR,
            self::USER_ERROR,
            self::RECOVERABLE_ERROR,
        ];
    }

    /**
     * Check if this error level is considered fatal.
     */
    public function isFatal(): bool
    {
        return in_array($this, self::fatalLevels(), true);
    }
}