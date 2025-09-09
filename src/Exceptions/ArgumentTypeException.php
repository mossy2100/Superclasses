<?php
declare(strict_types = 1);

namespace Superclasses\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when an argument has an invalid type.
 */
class ArgumentTypeException extends InvalidArgumentException
{
    /**
     * Create a new ArgumentTypeException.
     *
     * @param string $parameter_name The name of the parameter that failed validation, e.g. 'index'.
     * @param string $expected_type The expected type (e.g., 'int', 'string', 'callable').
     * @param mixed $actual_value The actual value that was provided (optional, for debugging).
     */
    public function __construct(string $parameter_name, string $expected_type, mixed $actual_value = null)
    {
        $message = "Parameter '$parameter_name' must be of type $expected_type";

        if (func_num_args() > 2) {
            $actual_type = get_debug_type($actual_value);
            $message .= ", $actual_type given.";
        }
        else {
            $message .= ".";
        }

        parent::__construct($message);
    }
}
