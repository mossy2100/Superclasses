<?php

declare(strict_types = 1);

namespace Superclasses\Math;

use InvalidArgumentException;
use Stringable;
use OverflowException;

class Rational implements Stringable
{
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Properties

    /**
     * The numerator.
     *
     * @var int
     */
    private(set) int $num;

    /**
     * The denominator.
     *
     * @var int
     */
    private(set) int $den;

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Constructor

    /**
     * Constructor.
     *
     * @param int $num The numerator. Defaults to 0.
     * @param int $den The denominator. Defaults to 1.
     */
    public function __construct(int $num = 0, int $den = 1)
    {
        // Check for zero denominator.
        if ($den === 0) {
            throw new InvalidArgumentException("Denominator cannot be zero.");
        }

        // Simplify the fraction.
        [$num, $den] = self::simplify($num, $den);

        // Set the properties.
        $this->num = $num;
        $this->den = $den;
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Factory methods

    /**
     * Create a rational number from a float using continued fractions.
     * This finds the rational with the smallest denominator that equals the provided float.
     * If an exact match is not found, the method will return the closest approximation with a denominator less than
     * or equal to $max_den. This is likely to be a more useful result than an exception, and limits the time spent
     * in the method.
     *
     * @param float $value The float value.
     * @param int $max_den Maximum allowed denominator (default: 1000000).
     * @return self The equivalent rational number.
     */
    public static function fromFloat(float $value, int $max_den = 1000000): self
    {
        // Check maximum denominator is positive.
        if ($max_den < 1) {
            throw new InvalidArgumentException("Maximum denominator must be positive.");
        }

        // Handle zero.
        if ($value == 0) {
            return new self();
        }

        // Handle integers.
        if ($value == (int)$value) {
            return new self((int)$value);
        }

        // Handle negative numbers.
        $sign = Numbers::sign($value, false);
        $value = abs($value);

        // Initialize convergents.
        $h0 = 1;
        $h1 = 0;
        $k0 = 0;
        $k1 = 1;

        // Initialize the initial approximation.
        $x = $value;

        // Track the best approximation found so far.
        $h_best = $value < 0.5 ? 0 : 1;
        $k_best = 1;
        $min_error = abs($h_best - $value);

        // Loop until done.
        while (true) {
            // Extract integer part.
            $a = (int)floor($x);

            // Calculate next convergent
            $h_new = $a * $h0 + $h1;
            $k_new = $a * $k0 + $k1;

            // If denominator exceeds limit, return the best approximation found so far.
            if ($k_new > $max_den) {
                return new self($sign * $h_best, $k_best);
            }

            // Check if we've found an exact representation.
            $error = abs($h_new / $k_new - $value);
            if ($error == 0) {
                return new self($sign * $h_new, $k_new);
            }

            // Check if this convergent is better than the best so far.
            if ($error < $min_error) {
                $h_best = $h_new;
                $k_best = $k_new;
                $min_error = $error;
            }

            // Update convergents.
            $h1 = $h0;
            $h0 = $h_new;
            $k1 = $k0;
            $k0 = $k_new;

            // Calculate remainder.
            $rem = $x - $a;

            // If the remainder is 0, we're done.
            if ($rem == 0) {
                return new self($sign * $h0, $k0);
            }

            // Calculate next approximation.
            $x = 1.0 / $rem;
        }
    }

    /**
     * Parse a string into a rational number.
     *
     * @param string $s The string to parse.
     * @return self The parsed rational number.
     * @throws InvalidArgumentException If the string cannot be parsed as a rational number.
     */
    public static function parse(string $s): self
    {
        // Check for string that looks like a float.
        if (is_numeric($s)) {
            return self::fromFloat((float)$s);
        }

        // Check for string that looks like a fraction.
        $parts = explode('/', $s);
        if (count($parts) === 2) {
            $num_str = trim($parts[0]);
            $den_str = trim($parts[1]);
            $num_is_int = Numbers::tryParseInt($num_str, $num);
            $den_is_int = Numbers::tryParseInt($den_str, $den);
            if ($num_is_int && $den_is_int) {
                return new self($num, $den);
            }
        }

        throw new InvalidArgumentException("Invalid rational number: $s");
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Conversion methods

    /**
     * Convert the rational number to a float.
     *
     * @return float The equivalent float.
     */
    public function toFloat(): float
    {
        return $this->num / $this->den;
    }

    /**
     * Convert the rational number to a string. (Stringable implementation.)
     *
     * @return string The string representation of the rational number.
     */
    public function __toString(): string
    {
        return $this->num . ($this->den === 1 ? '' : '/' . $this->den);
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Arithmetic operations

    /**
     * Calculate the negative of this rational number.
     *
     * @return self A new rational number representing the negative.
     */
    public function neg(): self {
        return new self(-$this->num, $this->den);
    }

    /**
     * Add another value to this rational number.
     *
     * @param int|float|self $other The value to add.s
     * @return self A new rational number representing the sum.
     */
    public function add(int|float|self $other): self
    {
        $other = self::toRational($other);

        // (a/b) + (c/d) = (ad + bc) / (bd)
        $f = Numbers::multiplyIntegers($this->num, $other->den);
        $g = Numbers::multiplyIntegers($this->den, $other->num);
        $h = Numbers::addIntegers($f, $g);
        $k = Numbers::multiplyIntegers($this->den, $other->den);

        return new self($h, $k);
    }

    /**
     * Subtract another value from this rational number.
     *
     * @param int|float|self $other The value to subtract.
     * @return self A new rational number representing the difference.
     */
    public function sub(int|float|self $other): self
    {
        return $this->add($other->neg());
    }

    /**
     * Calculate the reciprocal of this rational number.
     *
     * @return self A new rational number representing the reciprocal.
     */
    public function inv(): self {
        // Guard.
        if ($this->num === 0) {
            throw new InvalidArgumentException("Cannot take reciprocal of zero.");
        }

        return new self($this->den, $this->num);
    }

    /**
     * Multiply this rational number by another value.
     *
     * @param int|float|self $other The value to multiply by.
     * @return self A new rational number representing the product.
     * @throws OverflowException If the product would overflow an integer.
     */
    public function mul(int|float|self $other): self
    {
        $other = self::toRational($other);

        // Cross-cancel before multiplying: (a/b) * (c/d)
        // Cancel gcd(a,d) from a and d
        // Cancel gcd(b,c) from b and c
        $gcd1 = Numbers::gcd(abs($this->num), abs($other->den));
        $gcd2 = Numbers::gcd(abs($this->den), abs($other->num));

        $a = intdiv($this->num, $gcd1);
        $b = intdiv($this->den, $gcd2);
        $c = intdiv($other->num, $gcd2);
        $d = intdiv($other->den, $gcd1);

        // Now multiply the reduced terms: (a/b) * (c/d) = ac/bd
        $h = Numbers::multiplyIntegers($a, $c);
        $k = Numbers::multiplyIntegers($b, $d);

        return new self($h, $k);
    }

    /**
     * Divide this rational number by another value.
     *
     * @param int|float|self $other The value to divide by.
     * @return self A new rational number representing the quotient.
     * @throws InvalidArgumentException If dividing by zero.
     */
    public function div(int|float|self $other): self
    {
        // Guard.
        $other = self::toRational($other);
        if ($other->num === 0) {
            throw new InvalidArgumentException("Cannot divide by zero.");
        }

        return $this->mul($other->inv());
    }

    /**
     * Raise this rational number to an integer power using exponentiation by squaring.
     *
     * @param int $exponent The integer exponent.
     * @return self A new rational number representing the result.
     * @throws InvalidArgumentException If raising zero to a negative power.
     */
    public function pow(int $exponent): self
    {
        // Any number to the power of 0 is 1, including 0.
        // 0^0 can be considered undefined, but many programming languages (including PHP) return 1.
        if ($exponent === 0) {
            return new self(1);
        }

        // Handle 0 base.
        if ($this->num === 0) {
            // 0 to the power of a negative exponent is invalid (effectively division by zero).
            if ($exponent < 0) {
                throw new InvalidArgumentException("Cannot raise zero to a negative power.");
            }

            // 0 to the power of a positive exponent is 0.
            return new self(0);
        }

        // Handle negative exponents by taking reciprocal.
        if ($exponent < 0) {
            return $this->inv()->pow(-$exponent);
        }

        // Handle positive exponents using exponentiation by squaring.
        return self::powSqr($this, $exponent);
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Comparison methods

    /**
     * Compare a rational number with another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return int Returns -1 if this < other, 0 if equal, 1 if this > other.
     */
    public function compare(int|float|self $other): int
    {
        $other = self::toRational($other);

        try {
            // Cross multiply: compare a*d with b*c for a/b vs c/d.
            $left = Numbers::multiplyIntegers($this->num, $other->den);
            $right = Numbers::multiplyIntegers($this->den, $other->num);
        }
        catch (OverflowException) {
            // In case of overflow, compare equivalent floating point values.
            // This could return 0 if the two rationals convert to the same float, but that should be very unlikely.
            $left = $this->toFloat();
            $right = $other->toFloat();
        }

        return $left <=> $right;
    }

    /**
     * Check if this rational number equals another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return bool True if equal, false otherwise.
     */
    public function eq(int|float|self $other): bool
    {
        return $this->compare($other) === 0;
    }

    /**
     * Check if this rational number is less than another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return bool True if less than, false otherwise.
     */
    public function lt(int|float|self $other): bool
    {
        return $this->compare($other) === -1;
    }

    /**
     * Check if this rational number is greater than another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return bool True if greater than, false otherwise.
     */
    public function gt(int|float|self $other): bool
    {
        return $this->compare($other) === 1;
    }

    /**
     * Check if this rational number is less than or equal to another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return bool True if less than or equal to, false otherwise.
     */
    public function lte(int|float|self $other): bool
    {
        return $this->compare($other) !== 1;
    }

    /**
     * Check if this rational number is greater than or equal to another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return bool True if greater than or equal to, false otherwise.
     */
    public function gte(int|float|self $other): bool
    {
        return $this->compare($other) !== -1;
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Helper methods (private static)

    /**
     * Reduce a fraction and ensure the denominator is positive.
     *
     * @param int $num The numerator.
     * @param int $den The denominator.
     * @return int[] The simplified numerator and denominator.
     */
    private static function simplify(int $num, int $den): array
    {
        // Simplify zero.
        if ($num === 0) {
            return [0, 1];
        }

        // Calculate the GCD.
        $gcd = Numbers::gcd(abs($num), abs($den));

        // Reduce the fraction if necessary.
        if ($gcd > 1) {
            $num = intdiv($num, $gcd);
            $den = intdiv($den, $gcd);
        }

        // Ensure the denominator is positive.
        if ($den < 0) {
            [$num, $den] = [-$num, -$den];
        }

        // Return the simplified fraction.
        return [$num, $den];
    }

    /**
     * Convert a number into a Rational object, if it isn't one already.
     *
     * @param int|float|self $value The number to convert.
     * @return self The converted Rational.
     * @throws InvalidArgumentException If the number cannot be converted to a Rational.
     */
    private static function toRational(int|float|self $value): self
    {
        // If the value is already a Rational, return it as-is.
        if ($value instanceof self) {
            return $value;
        }

        // Check for integer.
        if (is_int($value)) {
            return new self($value);
        }

        // Convert float to Rational.
        return self::fromFloat($value);
    }

    /**
     * Compute base^exponent using exponentiation by squaring.
     *
     * @param self $base The base rational number.
     * @param int $exponent The positive exponent.
     * @return self The result.
     */
    private static function powSqr(self $base, int $exponent): self
    {
        // Recursion termination.
        if ($exponent === 1) {
            return $base;
        }

        // Even exponent: (a^n)^2 = a^(2n)
        if ($exponent % 2 === 0) {
            $half = self::powSqr($base, intdiv($exponent, 2));
            return $half->mul($half);
        }

        // Odd exponent: a^n = a * a^(n-1)
        return $base->mul(self::powSqr($base, $exponent - 1));
    }

    // endregion
}
