<?php

declare(strict_types = 1);

namespace Superclasses\Math;

use Random\RandomException;
use OverflowException;
use InvalidArgumentException;

class Numbers
{
    // region Constructor

    /**
     * Private constructor to prevent instantiation.
     */
    private function __construct()
    {
    }

    // endregion

    // region Inspection methods

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
    public static function isNegativeZero(float $x): bool
    {
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
    public static function isPositiveZero(float $x): bool
    {
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
    public static function isNegative(float $value): bool
    {
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
    public static function isPositive(float $value): bool
    {
        return !is_nan($value) && ($value > 0 || self::isPositiveZero($value));
    }

    // endregion

    // region Sign methods

    /**
     * Sign function.
     *
     * @param int|float $value The number whose sign to check.
     * @param bool $zeroForZero If true (default), returns 0 for zero; otherwise, return the sign of the zero.
     * @return int 1 if the number is positive, -1 if negative, and 0, 1, or -1 (depending on second argument) if 0.
     */
    public static function sign(int|float $value, bool $zeroForZero = true): int
    {
        // Check for positive.
        if ($value > 0) {
            return 1;
        }

        // Check for negative.
        if ($value < 0) {
            return -1;
        }

        // Return result for 0.
        return $zeroForZero ? 0 : (self::isNegativeZero($value) ? -1 : 1);
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
            throw new InvalidArgumentException("NaN is not allowed for either parameter.");
        }

        return abs($num) * self::sign($sign_source, false);
    }

    // endregion

    // region Integer arithmetic

    /**
     * Add two integers with overflow check.
     *
     * @param int $a The first integer.
     * @param int $b The second integer.
     * @return int The added integers if no overflow occurs.
     * @throws OverflowException If the addition results in overflow.
     */
    public static function intAdd(int $a, int $b): int
    {
        // Do the addition.
        $c = $a + $b;

        // Check for overflow.
        if (is_float($c)) {
            throw new OverflowException("Overflow in integer addition.");
        }

        // Return the result.
        return $c;
    }

    /**
     * Subtract one integer from another with overflow check.
     *
     * @param int $a The first integer.
     * @param int $b The second integer.
     * @return int The result of subtracting the second integer from the first, if no overflow occurs.
     * @throws OverflowException If the subtraction results in overflow.
     */
    public static function intSub(int $a, int $b): int
    {
        // Do the subtraction.
        $c = $a - $b;

        // Check for overflow.
        if (is_float($c)) {
            throw new OverflowException("Overflow in integer subtraction.");
        }

        // Return the result.
        return $c;
    }

    /**
     * Multiply two integers with overflow check.
     *
     * @param int $a The first integer.
     * @param int $b The second integer.
     * @return int The multiplied integers if no overflow occurs.
     * @throws OverflowException If the multiplication results in overflow.
     */
    public static function intMul(int $a, int $b): int
    {
        // Do the multiplication.
        $c = $a * $b;

        // Check for overflow.
        if (is_float($c)) {
            throw new OverflowException("Overflow in integer multiplication.");
        }

        // Return the result.
        return $c;
    }

    /**
     * Raise one integer to the power of another with overflow check.
     *
     * @param int $a The first integer.
     * @param int $b The second integer. Must be non-negative.
     * @return int The result integer if no overflow occur.
     * @throws InvalidArgumentException If $b is negative.
     * @throws OverflowException If the exponentiation results in overflow.
     */
    public static function intPow(int $a, int $b): int
    {
        // Handle b < 0.
        if ($b < 0) {
            throw new InvalidArgumentException("Negative exponents are not supported.");
        }

        // Do the exponentiation.
        $c = $a ** $b;

        // Check for overflow.
        if (is_float($c)) {
            throw new OverflowException("Overflow in exponentiation.");
        }

        // Return the result.
        return $c;
    }

    // endregion

    // region Other methods

    /**
     * Try to convert a string to an equivalent integer.
     *
     * This method is stricter than PHP's built-in intval() function or (int) cast. The string must look exactly like
     * an integer, meaning digits only (with optional leading minus sign), and the string must represent an integer
     * within the valid range (i.e. PHP_MIN_INT..PHP_MAX_INT).
     *
     * @param string $s The string to convert.
     * @param int|null $i The converted integer, if successful, or null otherwise.
     * @return bool True if the conversion was successful, false otherwise.
     */
    public static function tryParseInt(string $s, ?int &$i): bool
    {
        // Convert the string to an integer.
        $parsed = (int)$s;

        // Convert back to a string to confirm the string is a valid integer.
        $ok = $s === (string)$parsed;

        // Set the parsed int value.
        $i = $ok ? $parsed : null;

        // Return the result.
        return $ok;
    }

    /**
     * Calculate the greatest common divisor of two integers.
     *
     * @param int $a The first integer.
     * @param int $b The second integer.
     * @return int The greatest common divisor.
     */
    public static function gcd(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);
        return $b === 0 ? $a : self::gcd($b, $a % $b);
    }

    // endregion

    // region Random numbers

    /**
     * Generate a random finite float.
     *
     * @return float The random float.
     * @throws RandomException If an appropriate source of randomness cannot be found.
     */
    public static function randFloat(): float {
        // Generate random floats until a finite value is found.
        // It's very unlikely the loop will repeat more than once or twice.
        do {
            $bytes = random_bytes(8);
            $float = unpack('d', $bytes)[1];
        } while (!is_finite($float));

        return $float;
    }

    // endregion
}
