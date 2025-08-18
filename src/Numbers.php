<?php

namespace Superclasses;

use ValueError;

class Numbers
{
    /**
     * Check if a value is a number, i.e. an integer or a float.
     * This varies from is_numeric(), which also returns true for numeric strings.
     *
     * @param mixed $value The value to check.
     * @return bool True if the value is a number, false otherwise.
     */
    public static function isNumber(mixed $value): bool
    {
        return is_int($value) || is_float($value);
    }

    /**
     * Check if a value is an unsigned integer.
     *
     * @param mixed $value The value to check.
     * @return bool True if the value is an unsigned integer, false otherwise.
     */
    public static function isUnsignedInt(mixed $value): bool
    {
        return is_int($value) && $value >= 0;
    }

    /**
     * Get the sign of a number.
     *
     * @param float $value The number.
     * @return int -1 if negative or -0.0, 1 if positive or 0.0.
     */
    public static function sign(float $value): int
    {
        // Guard. This method is only valid for numbers.
        if (is_nan($value)) {
            throw new ValueError("NaN is not a valid number.");
        }

        if ($value == 0) {
            // Distinguish +0.0 and -0.0 without warnings.
            return fdiv(1.0, $value) === -INF ? -1 : 1;
        }

        return $value < 0 ? -1 : 1;
    }

    /**
     * Copy the sign of one number to another.
     *
     * @param float $num The number to copy the sign to.
     * @param float $sign_source The number to copy the sign from.
     * @return float The number with the sign of $sign_source.
     */
    public static function copySign(float $num, float $sign_source): float
    {
        // Guard. This method is only valid for numbers.
        if (is_nan($num) || is_nan($sign_source)) {
            throw new ValueError("Both parameters must be numbers.");
        }

        return abs($num) * Numbers::sign($sign_source);
    }
}