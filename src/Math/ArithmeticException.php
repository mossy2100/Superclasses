<?php
declare(strict_types = 1);

namespace Superclasses\Math;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when an arithmetic operation cannot be completed successfully, e.g. division by zero.
 *
 * NB: For that particular use case, we should not use DivisionByZeroError, because error exceptions are not supposed
 * to be thrown by userland code.
 *
 * This exception should probably not be used for overflow (e.g. a result outside the range of int or float). For that,
 * use OverflowException.
 */
class ArithmeticException extends RuntimeException
{
    /**
     * Constructor.
     *
     * @param string $message The exception message.
     * @param int $code The exception code.
     * @param Throwable|null $previous The previous exception.
     */
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
