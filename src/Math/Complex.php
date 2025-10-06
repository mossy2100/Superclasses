<?php

declare(strict_types = 1);

namespace Superclasses\Math;

use Stringable;
use ArrayAccess;
use InvalidArgumentException;
use LogicException;
use DomainException;

/**
 * TODO Complete tests.
 */
final class Complex implements Stringable, ArrayAccess
{
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Properties

    /**
     * The real part of the complex number.
     *
     * @var float
     */
    private(set) float $real;

    /**
     * The imaginary part of the complex number.
     *
     * @var float
     */
    private(set) float $imag;

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

    /**
     * The backing field for the i property.
     *
     * @var Complex|null
     */
    private static ?self $_i = null;

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
     * @return self A new complex number representing the negative of this one.
     */
    public function neg(): self
    {
        return new self(-$this->real, -$this->imag);
    }

    /**
     * Add another complex number to this one.
     *
     * @param self|int|float $other The real or complex number to add.
     * @return self A new complex number representing the sum.
     */
    public function add(self|int|float $other): self
    {
        // Make sure $other is Complex.
        $other = self::toComplex($other);

        // Do the addition.
        return new self($this->real + $other->real, $this->imag + $other->imag);
    }

    /**
     * Subtract another complex number from this one.
     *
     * @param self|int|float $other The real or complex number to subtract.
     * @return self A new complex number representing the difference.
     */
    public function sub(self|int|float $other): self
    {
        // Make sure $other is Complex.
        $other = self::toComplex($other);

        // Do the subtraction.
        return new self($this->real - $other->real, $this->imag - $other->imag);
    }

    /**
     * Multiply this complex number by another.
     * Uses the formula: (a + bi)(c + di) = (ac - bd) + (ad + bc)i
     *
     * @param self|int|float $other The real or complex number to multiply by.
     * @return self A new complex number representing the product.
     */
    public function mul(self|int|float $other): self
    {
        // Make sure $other is Complex.
        $other = self::toComplex($other);

        // Do the multiplication.
        $a = $this->real;
        $b = $this->imag;
        $c = $other->real;
        $d = $other->imag;
        return new self($a * $c - $b * $d, $a * $d + $b * $c);
    }

    /**
     * Divide this complex number by another.
     * Uses the formula: (a + bi)/(c + di) = [(ac + bd) + (bc - ad)i]/(c² + d²)
     *
     * @param self|int|float $other The real or complex number to divide by.
     * @return self A new complex number representing the quotient.
     * @throws DomainException If the divisor is zero.
     */
    public function div(self|int|float $other): self
    {
        // Make sure $other is Complex.
        $other = self::toComplex($other);

        // Check for divide by zero.
        if ($other->eq(0)) {
            throw new DomainException("Cannot divide by 0.");
        }

        // Do the division.
        $a = $this->real;
        $b = $this->imag;
        $c = $other->real;
        $d = $other->imag;
        $f = ($c * $c) + ($d * $d);
        return new self(($a * $c + $b * $d) / $f, ($b * $c - $a * $d) / $f);
    }

    /**
     * Get the complex conjugate of this number.
     * s
     * @return self A new complex number representing the conjugate.
     */
    public function conj(): self
    {
        return new self($this->real, -$this->imag);
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Transcendental operations

    /**
     * Calculate the natural logarithm of a complex number.
     *
     * @return self A new complex number representing ln(z).
     * @throws DomainException If the complex number is 0.
     */
    public function ln(): self
    {
        // Check for ln(0), which is undefined.
        if ($this->eq(0)) {
            throw new DomainException("The logarithm of 0 is undefined.");
        }

        // Use shortcuts where possible.
        if ($this->eq(1)) {
            return new self(0);
        }
        elseif ($this->eq(2)) {
            return new self(M_LN2);
        }
        elseif ($this->eq(M_E)) {
            return new self(1);
        }
        elseif ($this->eq(M_PI)) {
            return new self(M_LNPI);
        }
        elseif ($this->eq(10)) {
            return new self(M_LN10);
        }

        // Calculate ln(z) = ln|z| + i*arg(z)
        return new self(log($this->mag), $this->phase);
    }

    /**
     * Calculate the logarithm of a complex number with the given base.
     * Uses the change of base formula: log_b(z) = ln(z) / ln(b)
     *
     * @param self|int|float $base The base for the logarithm.
     * @return self A new complex number representing log_b(z).
     * @throws DomainException If the base is 0, 1, or if this number is 0.
     */
    public function log(self|int|float $base): self
    {
        // Make sure $base is Complex.
        $base = self::toComplex($base);

        // Check for invalid base values.
        if ($base->eq(0)) {
            throw new DomainException("Logarithm base cannot be 0.");
        }
        if ($base->eq(1)) {
            throw new DomainException("Logarithm base cannot be 1.");
        }

        // Check for natural logarithm.
        if ($base->eq(M_E)) {
            return $this->ln();
        }

        // Use built-in constants for log_2(e) and log_10(e).
        if ($this->eq(M_E)) {
            if ($base->eq(2)) {
                return new self(M_LOG2E);
            }
            elseif ($base->eq(10)) {
                return new self(M_LOG10E);
            }
        }

        // Use built-in log() function when arguments are real.
        if ($this->isReal() && $base->isReal()) {
            return new self(log($this->real, $base->real));
        }

        // Compute log_b(z) = ln(z) / ln(b)
        return $this->ln()->div($base->ln());
    }

    /**
     * Calculate e^z where z is this complex number.
     *
     * @return self A new complex number representing e^z.
     */
    public function exp(): self
    {
        // Use shortcuts where possible.
        if ($this->eq(0)) {
            return new self(1);
        }
        elseif ($this->eq(M_LN2)) {
            return new self(2);
        }
        elseif ($this->eq(1)) {
            return new self(M_E);
        }
        elseif ($this->eq(M_LNPI)) {
            return new self(M_PI);
        }
        elseif ($this->eq(M_LN10)) {
            return new self(10);
        }

        // Check for Euler's identity e^iπ = -1
        if ($this->eq(new self(0, M_PI))) {
            return new self(-1);
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
     * @param self|int|float $other The real or complex number to raise this complex number to.
     * @return self A new complex number representing the result.
     * @throws InvalidArgumentException If attempting 0 raised to a negative or complex power.
     */
    public function pow(self|int|float $other): self
    {
        // Make sure $other is Complex.
        $other = self::toComplex($other);

        // Handle special cases.
        if ($this->eq(0)) {
            // Check for complex exponent.
            if (!$other->isReal()) {
                throw new InvalidArgumentException("Cannot raise 0 to a complex number.");
            }

            // Check for negative real exponent.
            if ($other->real < 0) {
                throw new InvalidArgumentException("Cannot raise 0 to a negative real number.");
            }

            // Check for 0 exponent.
            if ($other->eq(0)) {
                // Although mathematically 0^0 is undefined, return 1 for consistency with pow(0, 0).
                // This is a common result in many programming languages.
                // (Principle of least astonishment.)
                // @see https://en.wikipedia.org/wiki/Zero_to_the_power_of_zero
                return new self(1);
            }

            // The exponent is a positive real number. 0 raised to any positive real number is 0.
            return new self();
        }

        // Handle exponent = 0. Any non-zero number to power 0 is 1.
        if ($other->eq(0)) {
            return new self(1);
        }

        // Handle exponent = 1. Any number to power 1 is itself.
        if ($other->eq(1)) {
            return clone $this;
        }

        // Handle i^2 = -1.
        if ($this->eq(self::i()) && $other->eq(2)) {
            return new self(-1);
        }

        // Handle e^w. Skip unnecessary calls to ln() and mul().
        if ($this->eq(M_E)) {
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
     * @return self[] An array of Complex numbers representing all nth roots.
     * @throws InvalidArgumentException If n is not a positive integer.
     */
    public function roots(int $n): array
    {
        // Check for negative.
        if ($n <= 0) {
            throw new InvalidArgumentException("Root index must be a positive integer");
        }

        // Handle special case of 0.
        if ($this->eq(0)) {
            return [new self()];
        }

        // Calculate the magnitude of the roots.
        $root_mag = $this->mag ** (1.0 / $n);

        // Calculate all n roots.
        $roots = [];
        for ($k = 0; $k < $n; $k++) {
            $root_phase = ($this->phase + 2 * M_PI * $k) / $n;
            $roots[] = self::fromPolar($root_mag, $root_phase);
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
     * @param self|int|float $other The real or complex number to compare with.
     * @param float $epsilon The tolerance for floating-point comparison.
     * @return bool True if the numbers are equal within the tolerance.
     */
    public function eq(self|int|float $other, float $epsilon = PHP_FLOAT_EPSILON): bool
    {
        // Make sure $other is Complex.
        $other = self::toComplex($other);

        // Compare real and imaginary parts.
        return abs($this->real - $other->real) < $epsilon &&
               abs($this->imag - $other->imag) < $epsilon;
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Factory methods

    /**
     * Get the imaginary unit.
     *
     * @return self A complex number equal to the imaginary unit.
     */
    public static function i(): self
    {
        return self::$_i ??= new self(0, 1);
    }

    /**
     * Convert the input value to a complex number, if not already.
     *
     * @param self|int|float $z The real or complex value.
     * @return self The equivalent complex value.
     */
    public static function toComplex(self|int|float $z): self
    {
        return $z instanceof self ? $z : new self($z);
    }

    /**
     * Create a complex number from polar coordinates.
     *
     * @param int|float $mag The magnitude (distance from origin).
     * @param int|float $phase The phase angle in radians.
     * @return self A new complex number.
     */
    public static function fromPolar(int|float $mag, int|float $phase): self
    {
        // Construct the new Complex.
        $z = new self($mag * cos($phase), $mag * sin($phase));

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
     * @return self The parsed complex number
     * @throws InvalidArgumentException If the string cannot be parsed
     */
    public static function parse(string $str): self
    {
        // Remove all whitespace
        $str = preg_replace('/\s+/', '', $str);

        // Handle empty string
        if ($str === '') {
            throw new InvalidArgumentException("Cannot parse empty string as complex number.");
        }

        // Handle pure real numbers (no imaginary unit)
        if (is_numeric($str)) {
            return new self((float)$str, 0);
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

            return new self(0, $imag);
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

        return new self($real, $imag);
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
     * Unsupported as this class is immutable.
     *
     * @param mixed $offset The offset to set.
     * @param mixed $value The value to set.
     * @throws LogicException If called.
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException("Complex values are immutable.");
    }

    /**
     * Unsupported as this class is immutable.
     *
     * @param mixed $offset The offset to unset.
     * @throws LogicException If called.
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException("Complex values are immutable.");
    }
}
