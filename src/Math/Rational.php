<?php

declare(strict_types = 1);

namespace Superclasses\Math;

use Stringable;
use InvalidArgumentException;
use OverflowException;
use RangeException;
use DomainException;

final class Rational implements Stringable
{
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

    // region Factory methods

    /**
     * Create a rational number from a real number using continued fractions.
     * This finds the simplest rational that equals the provided number (or is as close as is practical).
     *
     * If an exact match is not found, the method will return the closest approximation with a denominator less than
     * or equal to $max_den. This is likely to be a more useful result than an exception, and limits the time spent
     * in the method.
     *
     * Note that for values with a magnitude less than the smallest possible rational (1/PHP_INT_MAX), or greater than
     * the largest possible rational number (PHP_INT_MAX/1), an exception will be thrown.
     *
     * If you find the method to be slow in your environment, reduce the value of $max_den, but it should be ok.
     * In development the method runs super fast, but that was done on a high-end gaming laptop.
     * Tests indicate a $max_den of 200 million is sufficient for exact round-trip conversion between float and
     * Rational for e, pi, tau, and common square roots and fractions.
     *
     * Float representation limits can cause inexact round-trip conversions for values very close to integers.
     *
     * This method accepts ints as well as floats because most integers larger than 2^53 cannot be represented
     * exactly as floats, and we want it to work correctly with those numbers, too. Float conversion would cause a
     * loss of precision. This problem only exists on a 64-bit platform where integer magnitude is up to 2^63.
     *
     * @param float|int $value The real number value.
     * @param int $max_den Maximum allowed denominator.
     * @return self The equivalent rational number.
     * @throws InvalidArgumentException If the value is infinite or NaN, or is outside the valid convertible range,
     * or if the maximum denominator is negative.
     */
    public static function fromNumber(float|int $value, int $max_den = PHP_INT_MAX): self
    {
        // Check for infinite or NaN.
        if (!is_finite($value)) {
            throw new InvalidArgumentException("Cannot convert an infinity or NaN to a rational number.");
        }

        // Check maximum denominator is positive.
        if ($max_den < 1) {
            throw new InvalidArgumentException("The maximum denominator must be positive.");
        }

        // Shortcut. Handle integers.
        if ($value == (int)$value) {
            return new self((int)$value);
        }

        // Check for values outside the valid range for integers.
        if ($value < PHP_INT_MIN || $value > PHP_INT_MAX) {
            throw new InvalidArgumentException("The value is outside the valid range for integers and thus rational numbers.");
        }

        // Set up for the algorithm.
        $sign = Numbers::sign($value, false);
        $value = abs($value);

        // Check for values smaller than the smallest possible rational number (1/PHP_INT_MAX).
        if ($value < 1 / PHP_INT_MAX) {
            throw new InvalidArgumentException("The value is too small to be represented as a rational number.");
        }

        // Initialize convergents.
        $h0 = 1;
        $h1 = 0;
        $k0 = 0;
        $k1 = 1;

        // Set the initial approximation.
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
        // Check for string that looks like a number.
        if (is_numeric($s)) {
            return self::fromNumber((float)$s);
        }

        // Check for string that looks like a fraction.
        $parts = explode('/', $s);
        if (count($parts) === 2) {
            $num_is_int = Numbers::tryParseInt(trim($parts[0]), $num);
            $den_is_int = Numbers::tryParseInt(trim($parts[1]), $den);
            if ($num_is_int && $den_is_int) {
                return new self($num, $den);
            }
        }

        throw new InvalidArgumentException("Invalid rational number: $s");
    }

    /**
     * Convert a number or string into a Rational, if it isn't one already.
     *
     * This serves as a helper method used by many of the arithmetic methods in this class, but may have utility
     * as a general-purpose conversion method elsewhere.
     *
     * @param int|float|string|self $value The number to convert.
     * @return self The equivalent Rational.
     * @throws InvalidArgumentException If the number is NaN or infinite.
     */
    public static function toRational(int|float|string|self $value): self
    {
        // Check for Rational.
        if ($value instanceof self) {
            return $value;
        }

        // Check for string.
        if  (is_string($value)) {
            return self::parse($value);
        }

        // Must be int or float.
        return self::fromNumber($value);
    }

    // endregion

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
     * Convert the rational number to an int.
     *
     * @return int The closest integer, rounding towards zero.
     */
    public function toInt(): int
    {
        return intdiv($this->num, $this->den);
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

    // region Arithmetic operations

    /**
     * Calculate the negative of this rational number.
     *
     * @return self A new rational number representing the negative.
     */
    public function neg(): self
    {
        return new self(-$this->num, $this->den);
    }

    /**
     * Add another value to this rational number.
     *
     * @param int|float|self $other The value to add.
     * @return self A new rational number representing the sum.
     * @throws OverflowException If the result would overflow an integer.
     */
    public function add(int|float|self $other): self
    {
        $other = self::toRational($other);

        // (a/b) + (c/d) = (ad + bc) / (bd)
        $f = Numbers::intMul($this->num, $other->den);
        $g = Numbers::intMul($this->den, $other->num);
        $h = Numbers::intAdd($f, $g);
        $k = Numbers::intMul($this->den, $other->den);

        return new self($h, $k);
    }

    /**
     * Subtract another value from this rational number.
     *
     * @param int|float|self $other The value to subtract.
     * @return self A new rational number representing the difference.
     * @throws OverflowException If the result would overflow an integer.
     */
    public function sub(int|float|self $other): self
    {
        $other = self::toRational($other);

        // (a/b) - (c/d) = (ad - bc) / (bd)
        $f = Numbers::intMul($this->num, $other->den);
        $g = Numbers::intMul($this->den, $other->num);
        $h = Numbers::intSub($f, $g);
        $k = Numbers::intMul($this->den, $other->den);

        return new self($h, $k);
    }

    /**
     * Calculate the reciprocal of this rational number.
     *
     * @return self A new rational number representing the reciprocal.
     */
    public function inv(): self
    {
        // Guard.
        if ($this->num === 0) {
            throw new DomainException("Cannot take reciprocal of zero.");
        }

        return new self($this->den, $this->num);
    }

    /**
     * Multiply this rational number by another value.
     *
     * @param int|float|self $other The value to multiply by.
     * @return self A new rational number representing the product.
     * @throws OverflowException If the result would overflow an integer.
     */
    public function mul(int|float|self $other): self
    {
        $other = self::toRational($other);

        // Cross-cancel before multiplying: (a/b) * (c/d)
        // Cancel gcd(a,d) from a and d
        // Cancel gcd(b,c) from b and c
        $gcd1 = Numbers::gcd($this->num, $other->den);
        $gcd2 = Numbers::gcd($this->den, $other->num);

        $a = intdiv($this->num, $gcd1);
        $b = intdiv($this->den, $gcd2);
        $c = intdiv($other->num, $gcd2);
        $d = intdiv($other->den, $gcd1);

        // Now multiply the reduced terms: (a/b) * (c/d) = ac/bd
        $h = Numbers::intMul($a, $c);
        $k = Numbers::intMul($b, $d);

        return new self($h, $k);
    }

    /**
     * Divide this rational number by another value.
     *
     * @param int|float|self $other The value to divide by.
     * @return self A new rational number representing the quotient.
     * @throws DomainException If dividing by zero.
     * @throws OverflowException If the result would overflow an integer.
     */
    public function div(int|float|self $other): self
    {
        // Guard.
        $other = self::toRational($other);
        if ($other->num === 0) {
            throw new DomainException("Cannot divide by zero.");
        }

        return $this->mul($other->inv());
    }

    /**
     * Raise this rational number to an integer power.
     *
     * @param int $exponent The integer exponent.
     * @return self A new rational number representing the result.
     * @throws DomainException If raising zero to a negative power.
     * @throws OverflowException If the result would overflow an integer.
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
                throw new DomainException("Cannot raise zero to a negative power.");
            }

            // 0 to the power of a positive exponent is 0.
            return new self(0);
        }

        // Handle negative exponents by taking reciprocal.
        if ($exponent < 0) {
            return $this->inv()->pow(-$exponent);
        }

        // Calculate new numerator and denominator with overflow checks.
        $h = Numbers::intPow($this->num, $exponent);
        $k = Numbers::intPow($this->den, $exponent);

        // Return the result.
        return new self($h, $k);
    }

    /**
     * Calculate the absolute value of this rational number.
     *
     * @return self A new rational number representing the absolute value.
     */
    public function abs(): self
    {
        return new self(abs($this->num), $this->den);
    }

    /**
     * Find the closest integer less than or equal to the rational number.
     *
     * @return int The floored value.
     */
    public function floor(): int {
        if ($this->den === 1) {
            return $this->num;
        }
        $q = intdiv($this->num, $this->den);
        return $this->num < 0 ? $q - 1 : $q;
    }

    /**
     * Find the closest integer greater than or equal to the rational number.
     *
     * @return int The ceiling value.
     */
    public function ceil(): int {
        if ($this->den === 1) {
            return $this->num;
        }
        $q = intdiv($this->num, $this->den);
        return $this->num > 0 ? $q + 1 : $q;
    }

    /**
     * Find the integer closest to the rational number.
     *
     * The rounding method used here is "half away from zero", to match the default rounding mode used by PHP's
     * round() function. A future version of this method could include a RoundingMode parameter.
     *
     * @return int The closest integer.
     */
    public function round(): int {
        if ($this->den === 1) {
            return $this->num;
        }

        $q = intdiv($this->num, $this->den);
        $r = $this->num % $this->den;

        // Round away from zero if remainder ≥ half denominator.
        return (abs($r) * 2 >= $this->den) ? ($this->num > 0 ? $q + 1 : $q - 1) : $q;
    }

    // endregion

    // region Comparison methods

    /**
     * Compare a rational number with another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return int Returns -1 if this < other, 0 if equal, 1 if this > other.
     */
    public function cmp(int|float|self $other): int
    {
        $other = self::toRational($other);

        // If denominators are equal, just compare numerators.
        if ($this->den === $other->den) {
            $left = $this->num;
            $right = $other->num;
        }
        else {
            try {
                // Cross multiply: compare a*d with b*c for a/b vs c/d.
                $left = Numbers::intMul($this->num, $other->den);
                $right = Numbers::intMul($this->den, $other->num);
            } catch (OverflowException) {
                // In case of overflow, compare equivalent floating point values.
                // NB: This could produce a result of 0 (equal) if two rationals that are actually different convert to
                // the same float, which is possible for values with a magnitude greater than or equal to 2^53 (64-bit
                // platforms only).
                $left = $this->toFloat();
                $right = $other->toFloat();
            }
        }

        // The spaceship operator's contract only guarantees sign, not specific values. Normalize to -1, 0, or 1 for
        // predictable behavior used by other comparison methods.
        return Numbers::sign($left <=> $right);
    }

    /**
     * Check if this rational number equals another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return bool True if equal, false otherwise.
     */
    public function eq(int|float|self $other): bool
    {
        return $this->cmp($other) === 0;
    }

    /**
     * Check if this rational number is less than another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return bool True if less than, false otherwise.
     */
    public function lt(int|float|self $other): bool
    {
        return $this->cmp($other) === -1;
    }

    /**
     * Check if this rational number is greater than another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return bool True if greater than, false otherwise.
     */
    public function gt(int|float|self $other): bool
    {
        return $this->cmp($other) === 1;
    }

    /**
     * Check if this rational number is less than or equal to another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return bool True if less than or equal to, false otherwise.
     */
    public function lte(int|float|self $other): bool
    {
        return $this->cmp($other) !== 1;
    }

    /**
     * Check if this rational number is greater than or equal to another number.
     *
     * @param int|float|self $other The number to compare with.
     * @return bool True if greater than or equal to, false otherwise.
     */
    public function gte(int|float|self $other): bool
    {
        return $this->cmp($other) !== -1;
    }

    // endregion

    // region Helper methods (private static)

    /**
     * Convert a fraction to its canonical form.
     *
     * 1. Use canonical form for 0 (0/1).
     * 2. Reduce fraction to its simplest form.
     * 3. Ensure denominator is positive.
     *
     * @param int $num The numerator.
     * @param int $den The denominator.
     * @return int[] The simplified numerator and denominator.
     */
    private static function simplify(int $num, int $den): array
    {
        // Check for 0.
        if ($num === 0) {
            return [0, 1];
        }

        // Check for 1.
        if ($num === $den) {
            return [1, 1];
        }

        // Check for -1.
        if ($num === -$den) {
            return [-1, 1];
        }

        // Handle PHP_INT_MIN specially because otherwise the call to abs() would overflow.
        // We may be able to solve this by dividing by 2. PHP_INT_MIN equals -2^n (where n is the bit width minus 1),
        // therefore 2 is the only common factor to consider.
        if (($num === PHP_INT_MIN && $den % 2 === 0) || ($den === PHP_INT_MIN && $num % 2 === 0)) {
            $num = intdiv($num, 2);
            $den = intdiv($den, 2);
        }

        // Handle numerator of PHP_INT_MIN.
        if ($num === PHP_INT_MIN) {
            // We can return the values as-is if the denominator is positive. We know the fraction will already be
            // reduced to its simplest form due to the previous block. If we're here, the denominator is odd.
            if ($den > 0) {
                return [$num, $den];
            }

            // Since the canonical form has a positive denominator, we can't return this as-is, and have to throw an
            // exception.
            throw new RangeException("Cannot simplify a rational with a numerator of PHP_INT_MIN and a negative denominator.");
        }

        // Handle denominator of PHP_INT_MIN.
        if ($den === PHP_INT_MIN) {
            // Since the canonical form has a positive denominator, we can't return this as-is, and have to throw an
            // exception.
            throw new RangeException("Cannot simplify a rational with a denominator of PHP_INT_MIN.");
        }

        // Calculate the GCD.
        $gcd = Numbers::gcd($num, $den);

        // Reduce the fraction if necessary.
        if ($gcd > 1) {
            $num = intdiv($num, $gcd);
            $den = intdiv($den, $gcd);
        }

        // Ensure the denominator is positive.
        if ($den < 0) {
            return [-$num, -$den];
        }

        // Return the simplified fraction.
        return [$num, $den];
    }

    // endregion
}
