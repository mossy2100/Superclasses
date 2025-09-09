<?php

declare(strict_types = 1);

namespace Superclasses\Math;

use UnexpectedValueException;

class Numbers
{
    /**
     * Private constructor to prevent instantiation.
     */
    private function __construct() {}

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
     * Determines if a floating-point number is negative zero (-0.0).
     *
     * In IEEE-754 floating-point arithmetic, negative zero is a distinct value from
     * positive zero, though they compare as equal. This method provides a way to
     * distinguish between them.
     *
     * The method works by dividing 1.0 by the input value. For negative zero,
     * this division results in negative infinity (-INF).
     *
     * @param float $x The floating-point number to check.
     * @return bool True if the value is negative zero (-0.0), false otherwise.
     */
    public static function isNegativeZero(float $x): bool {
        // Using fdiv() to avoid a division by zero error.
        return $x == 0.0 && fdiv(1.0, $x) === -INF;
    }

    /**
     * Determines if a floating-point number is positive zero (+0.0).
     *
     * In IEEE-754 floating-point arithmetic, positive zero is a distinct value from
     * negative zero, though they compare as equal. This method provides a way to
     * distinguish between them.
     *
     * The method works by dividing 1.0 by the input value. For positive zero,
     * this division results in positive infinity (INF).
     *
     * @param float $x The floating-point number to check.
     * @return bool True if the value is positive zero (+0.0), false otherwise.
     */
    public static function isPositiveZero(float $x): bool {
        // Using fdiv() to avoid a division by zero error.
        return $x == 0.0 && fdiv(1.0, $x) === INF;
    }

    /**
     * Check if a number is negative.
     *
     * NB: This method returns true for the IEEE special values -0.0 (negative zero) and -INF (negative infinity).
     *
     * @param float $value The value to check.
     * @return bool True if the value is negative, false otherwise.
     */
    public static function isNegative(float $value): bool {
        return !is_nan($value) && ($value < 0 || self::isNegativeZero($value));
    }

    /**
     * Check if a number is positive.
     *
     * NB: This method returns true for the IEEE special values 0.0 (positive zero) and INF (positive infinity).
     *
     * @param float $value The value to check.
     * @return bool True if the value is positive, false otherwise.
     */
    public static function isPositive(float $value): bool {
        return !is_nan($value) && ($value > 0 || self::isPositiveZero($value));
    }

    /**
     * Get the sign of a number.
     *
     * NB: This method does not return 0 for 0 (which is neither negative nor positive), which might be a surprise.
     * Its main use case is for multiplying with a result so the result has the right sign.
     *
     * @param float $value The number.
     * @return int -1 if negative or -0.0, 1 if positive or 0.0.
     */
    public static function sign(float $value): int
    {
        // Guard. This method is only valid for numbers.
        if (is_nan($value)) {
            throw new UnexpectedValueException("NaN is not allowed.");
        }

        return self::isNegative($value) ? -1 : 1;
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
            throw new UnexpectedValueException("NaN is not allowed for either parameter.");
        }

        return abs($num) * self::sign($sign_source);
    }
}
