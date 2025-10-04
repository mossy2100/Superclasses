<?php

declare(strict_types = 1);

namespace Superclasses\Math;

use Stringable;
use ArrayAccess;
use InvalidArgumentException;
use Superclasses\Exceptions\ArithmeticException;

/**
 * TODO Complete tests.
 */
class Complex implements Stringable, ArrayAccess
{
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Properties

    /**
     * The backing field for the real property.
     *
     * @var float
     */
    private float $_real = 0;

    /**
     * The real part of the complex number.
     *
     * @var float
     */
    public float $real {
        get {
            return $this->_real;
        }

        set(float|int $value) {
            // Make sure value is a float.
            $value = (float)$value;

            // Make sure value is finite.
            if (!is_finite($value)) {
                throw new InvalidArgumentException("Complex numbers must have finite components.");
            }

            // Don't update unless necessary.
            if ($value !== $this->_real) {
                // Update backing field.
                $this->_real = $value;

                // Clear computed properties.
                $this->_mag = null;
                $this->_phase = null;
            }
        }
    }

    /**
     * The backing field for the imag property.
     *
     * @var float
     */
    private float $_imag = 0;

    /**
     * The imaginary part of the complex number.
     *
     * @var float
     */
    public float $imag {
        get {
            return $this->_imag;
        }

        set(float|int $value) {
            // Make sure value is a float.
            $value = (float)$value;

            // Make sure value is finite.
            if (!is_finite($value)) {
                throw new InvalidArgumentException("Complex numbers must have finite components.");
            }

            // Don't update unless necessary.
            if ($value !== $this->_imag) {
                // Update backing field.
                $this->_imag = $value;

                // Clear computed properties.
                $this->_mag = null;
                $this->_phase = null;
            }
        }
    }

    /**
     * The backing field for the mag property.
     *
     * @var ?float
     */
    private ?float $_mag = null;

    /**
     * The magnitude (a.k.a. absolute value or modulus) of this complex number.
     *
     * @var float
     */
    public float $mag {
        get {
            // Compute if necessary.
            if ($this->_mag === null) {
                $this->_mag = $this->isReal() ? abs($this->real) : hypot($this->real, $this->imag);
            }

            return $this->_mag;
        }
    }

    /**
     * The backing field for the phase property.
     *
     * @var ?float
     */
    private ?float $_phase = null;

    /**
     * The phase (a.k.a. argument) of this complex number in radians.
     *
     * @var float
     */
    public float $phase {
        get {
            // Compute if necessary.
            if ($this->_phase === null) {
                $this->_phase = $this->isReal() ? ($this->real < 0 ? M_PI : 0) : atan2($this->imag, $this->real);
            }

            return $this->_phase;
        }
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Constructor

    /**
     * Create a new complex number.
     *
     * @param int|float $real The real part of the complex number.
     * @param int|float $imag The imaginary part of the complex number.
     */
    public function __construct(int|float $real = 0, int|float $imag = 0)
    {
        $this->real = $real;
        $this->imag = $imag;
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Arithmetic operations

    /**
     * Negate a complex number.
     *
     * @return Complex A new complex number representing the negative of this one.
     */
    public function neg(): Complex
    {
        return new Complex(-$this->real, -$this->imag);
    }

    /**
     * Add another complex number to this one.
     *
     * @param Complex|int|float $other The real or complex number to add.
     * @return Complex A new complex number representing the sum.
     */
    public function add(Complex|int|float $other): Complex
    {
        // Make sure $other is Complex.
        $other = self::toComplex($other);

        // Do the addition.
        return new Complex($this->real + $other->real, $this->imag + $other->imag);
    }

    /**
     * Subtract another complex number from this one.
     *
     * @param Complex|int|float $other The real or complex number to subtract.
     * @return Complex A new complex number representing the difference.
     */
    public function sub(Complex|int|float $other): Complex
    {
        // Make sure $other is Complex.
        $other = self::toComplex($other);

        // Do the subtraction.
        return new Complex($this->real - $other->real, $this->imag - $other->imag);
    }

    /**
     * Multiply this complex number by another.
     * Uses the formula: (a + bi)(c + di) = (ac - bd) + (ad + bc)i
     *
     * @param Complex|int|float $other The real or complex number to multiply by.
     * @return Complex A new complex number representing the product.
     */
    public function mul(Complex|int|float $other): Complex
    {
        // Make sure $other is Complex.
        $other = self::toComplex($other);

        // Do the multiplication.
        $a = $this->real;
        $b = $this->imag;
        $c = $other->real;
        $d = $other->imag;
        return new Complex($a * $c - $b * $d, $a * $d + $b * $c);
    }

    /**
     * Divide this complex number by another.
     * Uses the formula: (a + bi)/(c + di) = [(ac + bd) + (bc - ad)i]/(c² + d²)
     *
     * @param Complex|int|float $other The real or complex number to divide by.
     * @return Complex A new complex number representing the quotient.
     * @throws ArithmeticException If the divisor is zero.
     */
    public function div(Complex|int|float $other): Complex
    {
        // Make sure $other is Complex.
        $other = self::toComplex($other);

        // Check for divide by zero.
        if ($other->equals(0)) {
            throw new ArithmeticException("Cannot divide by 0.");
        }

        // Do the division.
        $a = $this->real;
        $b = $this->imag;
        $c = $other->real;
        $d = $other->imag;
        $f = ($c * $c) + ($d * $d);
        return new Complex(($a * $c + $b * $d) / $f, ($b * $c - $a * $d) / $f);
    }

    /**
     * Get the complex conjugate of this number.
     * s
     * @return Complex A new complex number representing the conjugate.
     */
    public function conj(): Complex
    {
        return new Complex($this->real, -$this->imag);
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Transcendental operations

    /**
     * Calculate the natural logarithm of a complex number.
     *
     * @return Complex A new complex number representing ln(z).
     * @throws ArithmeticException If the complex number is 0.
     */
    public function ln(): Complex
    {
        // Check for ln(0), which is undefined.
        if ($this->equals(0)) {
            throw new ArithmeticException("The logarithm of 0 is undefined.");
        }

        // Use shortcuts where possible.
        if ($this->equals(1)) {
            return new Complex(0);
        }
        elseif ($this->equals(2)) {
            return new Complex(M_LN2);
        }
        elseif ($this->equals(M_E)) {
            return new Complex(1);
        }
        elseif ($this->equals(M_PI)) {
            return new Complex(M_LNPI);
        }
        elseif ($this->equals(10)) {
            return new Complex(M_LN10);
        }

        // Calculate ln(z) = ln|z| + i*arg(z)
        return new Complex(log($this->mag), $this->phase);
    }

    /**
     * Calculate the logarithm of a complex number with the given base.
     * Uses the change of base formula: log_b(z) = ln(z) / ln(b)
     *
     * @param Complex|int|float $base The base for the logarithm.
     * @return Complex A new complex number representing log_b(z).
     * @throws ArithmeticException If the base is 0, 1, or if this number is 0.
     */
    public function log(Complex|int|float $base): Complex
    {
        // Make sure $base is Complex.
        $base = self::toComplex($base);

        // Check for invalid base values.
        if ($base->equals(0)) {
            throw new ArithmeticException("Logarithm base cannot be 0.");
        }
        if ($base->equals(1)) {
            throw new ArithmeticException("Logarithm base cannot be 1.");
        }

        // Check for natural logarithm.
        if ($base->equals(M_E)) {
            return $this->ln();
        }

        // Use built-in constants for log_2(e) and log_10(e).
        if ($this->equals(M_E)) {
            if ($base->equals(2)) {
                return new Complex(M_LOG2E);
            }
            elseif ($base->equals(10)) {
                return new Complex(M_LOG10E);
            }
        }

        // Use built-in log() function when arguments are real.
        if ($this->isReal() && $base->isReal()) {
            return new Complex(log($this->real, $base->real));
        }

        // Compute log_b(z) = ln(z) / ln(b)
        return $this->ln()->div($base->ln());
    }

    /**
     * Calculate e^z where z is this complex number.
     *
     * @return Complex A new complex number representing e^z.
     */
    public function exp(): Complex
    {
        // Use shortcuts where possible.
        if ($this->equals(0)) {
            return new Complex(1);
        }
        elseif ($this->equals(M_LN2)) {
            return new Complex(2);
        }
        elseif ($this->equals(1)) {
            return new Complex(M_E);
        }
        elseif ($this->equals(M_LNPI)) {
            return new Complex(M_PI);
        }
        elseif ($this->equals(M_LN10)) {
            return new Complex(10);
        }

        // Check for Euler's identity e^iπ = -1
        if ($this->equals(new Complex(0, M_PI))) {
            return new Complex(-1);
        }

        // Uses Euler's formula: e^(a + bi) = e^a * (cos(b) + i*sin(b))
        return self::fromPolar(exp($this->real), $this->imag);
    }

    /**
     * Raise this complex number to a power.
     * This function can be multi-valued for certain base/exponent combinations.
     * For simplicity, only the principal value is returned.
     *
     * Single-valued cases:
     * - Any base raised to an integer exponent.
     * - Real positive base with real exponent.
     *
     * Multi-valued cases:
     * - Complex base with fractional exponent: z^(1/n)
     * - Negative real base with fractional exponent: (-2)^(1/3)
     * - Any base with complex exponent: z^(a+bi) where b ≠ 0
     *
     * @param Complex|int|float $other The real or complex number to raise this complex number to.
     * @return Complex A new complex number representing the result.
     * @throws InvalidArgumentException If attempting 0 raised to a negative or complex power.
     */
    public function pow(Complex|int|float $other): Complex
    {
        // Make sure $other is Complex.
        $other = self::toComplex($other);

        // Handle special cases.
        if ($this->equals(0)) {
            // Check for complex exponent.
            if (!$other->isReal()) {
                throw new InvalidArgumentException("Cannot raise 0 to a complex number.");
            }

            // Check for negative real exponent.
            if ($other->real < 0) {
                throw new InvalidArgumentException("Cannot raise 0 to a negative real number.");
            }

            // Check for 0 exponent.
            if ($other->equals(0)) {
                // Although mathematically 0^0 is undefined, return 1 for consistency with pow(0, 0).
                // This is a common result in many programming languages.
                // (Principle of least astonishment.)
                // @see https://en.wikipedia.org/wiki/Zero_to_the_power_of_zero
                return new Complex(1);
            }

            // The exponent is a positive real number. 0 raised to any positive real number is 0.
            return new Complex();
        }

        // Handle exponent = 0. Any non-zero number to power 0 is 1.
        if ($other->equals(0)) {
            return new Complex(1);
        }

        // Handle exponent = 1. Any number to power 1 is itself.
        if ($other->equals(1)) {
            return clone $this;
        }

        // Handle i^2 = -1.
        if ($this->equals(self::i()) && $other->equals(2)) {
            return new Complex(-1);
        }

        // Handle e^w. Skip unnecessary calls to ln() and mul().
        if ($this->equals(M_E)) {
            return $other->exp();
        }

        // Calculate z^w = e^(w * ln(z)).
        return $other->mul($this->ln())->exp();
    }

    /**
     * Calculate the nth roots of this complex number.
     * Returns all n complex roots using De Moivre's theorem.
     *
     * @param int $n The root to calculate (e.g., 2 for square root, 3 for cube root).
     * @return Complex[] An array of Complex numbers representing all nth roots.
     * @throws InvalidArgumentException If n is not a positive integer.
     */
    public function roots(int $n): array
    {
        // Check for negative.
        if ($n <= 0) {
            throw new InvalidArgumentException("Root index must be a positive integer");
        }

        // Handle special case of 0.
        if ($this->equals(0)) {
            return [new Complex()];
        }

        // Calculate the magnitude of the roots.
        $root_mag = $this->mag ** (1.0 / $n);

        // Calculate all n roots.
        $roots = [];
        for ($k = 0; $k < $n; $k++) {
            $root_phase = ($this->phase + 2 * M_PI * $k) / $n;
            $roots[] = Complex::fromPolar($root_mag, $root_phase);
        }

        return $roots;
    }

    /**
     * Calculate the square of this complex number.
     *
     * @return $this
     */
    public function sqr(): self
    {
        return $this->pow(2);
    }

    /**
     * Calculate the square root of this complex number.
     * Only the principal value is returned. For both square roots, call roots(2).
     *
     * @return $this
     */
    public function sqrt(): self
    {
        return $this->pow(0.5);
    }

    /**
     * Calculate the cube of this complex number.
     *
     * @return $this
     */
    public function cube(): self
    {
        return $this->pow(3);
    }

    /**
     * Calculate the cube root of this complex number.
     * Only the principal value is returned. For all cube roots, call roots(3).
     *
     * @return $this
     */
    public function cbrt(): self
    {
        return $this->pow(1 / 3);
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Comparison methods

    /**
     * Check if a complex number is real.
     *
     * @return bool True if the Complex is a real number, otherwise false.
     */
    public function isReal(): bool
    {
        return $this->imag == 0;
    }

    /**
     * Check if this complex number equals another.
     *
     * @param Complex|int|float $other The real or complex number to compare with.
     * @param float $epsilon The tolerance for floating-point comparison.
     * @return bool True if the numbers are equal within the tolerance.
     */
    public function equals(Complex|int|float $other, float $epsilon = PHP_FLOAT_EPSILON): bool
    {
        // Make sure $other is Complex.
        $other = self::toComplex($other);

        // Compare real and imaginary parts.
        return abs($this->real - $other->real) < $epsilon &&
               abs($this->imag - $other->imag) < $epsilon;
    }

    /**
     * Check if this complex number is the same as another.
     *
     * @param Complex $other The complex number to compare with.
     * @return bool True if the numbers are equal, otherwise false.
     */
    public function same(Complex $other): bool {
        return $this === $other;
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Factory methods

    /**
     * Get an imaginary unit.
     *
     * @return Complex A new complex number equal to the imaginary unit.
     */
    public static function i(): Complex
    {
        return new Complex(0, 1);
    }

    /**
     * Convert the input value to a complex number, if not already.
     *
     * @param Complex|int|float $z The real or complex value.
     * @return Complex The equivalent complex value.
     */
    public static function toComplex(Complex|int|float $z): Complex
    {
        return $z instanceof Complex ? $z : new Complex($z);
    }

    /**
     * Create a complex number from polar coordinates.
     *
     * @param int|float $mag The magnitude (distance from origin).
     * @param int|float $phase The phase angle in radians.
     * @return Complex A new complex number.
     */
    public static function fromPolar(int|float $mag, int|float $phase): Complex
    {
        // Construct the new Complex.
        $z = new Complex($mag * cos($phase), $mag * sin($phase));

        // We may as well remember the magnitude and phase since we know them already.
        $z->_mag = $mag;
        $z->_phase = $phase;

        return $z;
    }

    /**
     * Parse a string representation of a complex number.
     *
     * Supports various formats:
     * - Real numbers: "5", "-3.14", "0"
     * - Pure imaginary: "i", "-i", "3i", "-2.5j", "I", "J"
     * - Complex: "3+4i", "5-2j", "-1+i", "2.5-3.7I"
     * - Spaces allowed: "3 + 4i", "5 - 2j"
     * - Either order: "4i+3", "-2j+5"
     *
     * @param string $str The string to parse
     * @return Complex The parsed complex number
     * @throws InvalidArgumentException If the string cannot be parsed
     */
    public static function parse(string $str): Complex
    {
        // Remove all whitespace
        $str = preg_replace('/\s+/', '', $str);

        // Handle empty string
        if ($str === '') {
            throw new InvalidArgumentException("Cannot parse empty string as complex number.");
        }

        // Handle pure real numbers (no imaginary unit)
        if (is_numeric($str)) {
            return new Complex((float)$str, 0);
        }

        // Handle pure imaginary with or without coefficient: i, 3i, -2.5j, etc.
        $rx_num = '(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?';
        if (preg_match("/^([+-]?)((?:$rx_num)?)[ijIJ]$/", $str, $matches)) {
            // Handle cases where coefficient is omitted (like i or -i).
            $imag = $matches[2] === '' ? 1 : (float)$matches[2];

            // Apply signs to get final value.
            if ($matches[1] === '-') {
                $imag = -$imag;
            }

            return new Complex(0, $imag);
        }

        // Handle complex numbers with both real and imaginary parts.
        // Pattern real±imag.
        $pattern_real_first = "/^([+-]?)($rx_num)([+-])((?:$rx_num)?)[ijIJ]\$/";
        // Pattern imag±real.
        $pattern_imag_first = "/^([+-]?)((?:$rx_num)?)[ijIJ]([+-])($rx_num)\$/";

        if (preg_match($pattern_real_first, $str, $matches)) {
            $real_sign = $matches[1];
            $real_val = $matches[2];
            $imag_sign = $matches[3];
            $imag_val = $matches[4];
        }
        elseif (preg_match($pattern_imag_first, $str, $matches)) {
            $imag_sign = $matches[1];
            $imag_val = $matches[2];
            $real_sign = $matches[3];
            $real_val = $matches[4];
        }
        else {
            throw new InvalidArgumentException("Cannot parse '$str' as complex number.");
        }

        // Get the imaginary part. Handle cases where imaginary coefficient is omitted (like +i or -i).
        $imag = $imag_val === '' ? 1 :  (float)$imag_val;

        // Get the real part.
        $real = (float)$real_val;

        // Apply signs to get final values.
        if ($imag_sign === '-') {
            $imag = -$imag;
        }
        if ($real_sign === '-') {
            $real = -$real;
        }

        return new Complex($real, $imag);
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Stringable implementation

    /**
     * Convert the complex number to a string representation.
     *
     * @return string String representation in the form "a", "bi", "a + bi", or "a - bi".
     */
    public function __toString(): string
    {
        // Handle case for 0 imaginary part.
        if ($this->isReal()) {
            return (string)$this->real;
        }

        // Handle case for 0 real part and non-zero imaginary part.
        if ($this->real == 0) {
            if ($this->imag == 1) {
                return 'i';
            }
            elseif ($this->imag == -1) {
                return '-i';
            }
            else {
                return $this->imag . 'i';
            }
        }

        // Construct the string for the a + bi or a - bi form.
        $op = $this->imag > 0 ? ' + ' : ' - ';
        $abs = abs($this->imag);
        $imag = $abs == 1 ? '' : $abs;
        return $this->real . $op . $imag . 'i';
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Conversion methods

    /**
     * Convert the complex number to an array.
     *
     * @return array An array containing the real and imaginary parts of the complex number.
     */
    public function toArray(): array
    {
        return [$this->real, $this->imag];
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region ArrayAccess implementation

    /**
     * Check if the complex number has a given offset. Only 0 and 1 are valid offsets.
     *
     * @param mixed $offset The offset to check.
     * @return bool True if the offset exists, false otherwise.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $offset === 0 || $offset === 1;
    }

    /**
     * Get the value of the complex number at the given offset. Only 0 and 1 are valid offsets.
     *
     * @param mixed $offset The offset to retrieve.
     * @return int|float The value at the given offset.
     * @throws InvalidArgumentException If the offset is invalid.
     */
    public function offsetGet(mixed $offset): int|float
    {
        // Guard.
        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException("Invalid offset: $offset");
        }

        // Return the appropriate value.
        return $offset === 0 ? $this->real : $this->imag;
    }

    /**
     * Set the value of the complex number at the given offset. Only 0 and 1 are valid offsets.
     *
     * @param mixed $offset The offset to set.
     * @param mixed $value The value to set.
     * @throws InvalidArgumentException If the offset is invalid.
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Guard.
        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException("Invalid offset: $offset");
        }

        // Set the value.
        if ($offset === 0) {
            $this->real = $value;
        }
        else {
            $this->imag = $value;
        }
    }

    /**
     * Unset the value of the complex number at the given offset. Only 0 and 1 are valid offsets.
     * In this context, unsetting means setting the value to 0, not null, and not removing the offset.
     *
     * @param mixed $offset The offset to unset.
     * @throws InvalidArgumentException If the offset is invalid.
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        // Guard.
        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException("Invalid offset: $offset");
        }

        // Unset the value.
        if ($offset === 0) {
            $this->real = 0;
        }
        else {
            $this->imag = 0;
        }
    }
}
