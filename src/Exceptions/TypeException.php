<?php
declare(strict_types = 1);

namespace Superclasses\Exceptions;

use Throwable;
use LogicException;

/**
 * Exception thrown when a value has an invalid type.
 */
class TypeException extends LogicException
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

    /**
     * Create a new TypeException using information about the parameter and expected type.
     *
     * @param string $param_name The name of the parameter that failed validation, e.g. 'index'.
     * @param string $expected_type The expected type (e.g., 'int', 'string', 'callable').
     * @param mixed $param_value The actual value that was provided (optional, for debugging).
     */
    public static function create(string $param_name, string $expected_type, mixed $param_value = null): self
    {
        $message = "Parameter '$param_name' must be of type $expected_type";

        if (func_num_args() > 2) {
            $actual_type = get_debug_type($param_value);
            $message .= ", $actual_type given.";
        }
        else {
            $message .= ".";
        }

        return new self($message);
    }
}
