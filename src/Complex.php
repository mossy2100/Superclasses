<?php

declare(strict_types=1);

namespace Superclasses;

use Stringable;
use DivisionByZeroError, InvalidArgumentException, ArithmeticError;

/**
 * TODO
 * 4. Write tests.
 */

class Complex implements Stringable
{
    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Properties

    /**
     * The real part of the complex number.
     * 
     * @var float
     */
    private float $_real = 0;
    public float $real {
        get {
            return $this->_real;
        }

        set(float|int $value) {
            // Make sure value is a finite float.
            $value = (float)$value;
            if (!is_finite($value)) {
                throw new InvalidArgumentException("Complex numbers must have finite components.");
            }

            // Update backing field.
            $this->_real = $value;

            // Clear computed properties.
            $this->_mag = null;
            $this->_phase = null;
        }
    }

    /**
     * The imaginary part of the complex number.
     * 
     * @var float
     */
    private float $_imag = 0;
    public float $imag {
        get {
            return $this->_imag;
        }

        set(float|int $value) {
            // Make sure value is a finite float.
            $value = (float)$value;
            if (!is_finite($value)) {
                throw new InvalidArgumentException("Complex numbers must have finite components.");
            }

            // Update backing field.
            $this->_imag = $value;

            // Clear computed properties.
            $this->_mag = null;
            $this->_phase = null;
        }
    }

    /**
     * The magnitude (a.k.a. absolute value or modulus) of this complex number.
     * 
     * @var float
     */
    private ?float $_mag = null;
    public float $mag {
        get {
            // Compute if necessary.
            if ($this->_mag === null) {
                $this->_mag = $this->isReal() ?
                    abs($this->real) :
                    sqrt(($this->real * $this->real) + ($this->imag * $this->imag));
            }
            return $this->_mag;
        }
    }

    /**
     * The phase (a.k.a. argument) of this complex number in radians.
     * 
     * @var float
     */
    private ?float $_phase = null;
    public float $phase {
        get {
            // Compute if necessary.
            if ($this->_phase === null) {
                $this->_phase = $this->isReal() ?
                    ($this->real < 0 ? M_PI : 0) :
                    atan2($this->imag, $this->real);
            }
            return $this->_phase;
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Constructor

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

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Arithmetic operations

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
     * @throws DivisionByZeroError If the divisor is zero.
     */
    public function div(Complex|int|float $other): Complex
    {
        // Make sure $other is Complex.
        $other = self::toComplex($other);

        // Check for divide by zero.
        if ($other->eq(0)) {
            throw new DivisionByZeroError("Cannot divide by 0.");
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

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Transcendental operations

    /**
     * Calculate the natural logarithm of a complex number.
     * 
     * @return Complex A new complex number representing ln(z).
     * @throws ArithmeticError If the complex number is 0.
     */
    public function ln(): Complex
    {
        // Check for ln(0), which is undefined.
        if ($this->eq(0)) {
            throw new ArithmeticError("The logarithm of 0 is undefined.");
        }

        // Use shortcuts where possible.
        if ($this->eq(1)) {
            return new Complex(0);
        } elseif ($this->eq(2)) {
            return new Complex(M_LN2);
        } elseif ($this->eq(M_E)) {
            return new Complex(1);
        } elseif ($this->eq(M_PI)) {
            return new Complex(M_LNPI);
        } elseif ($this->eq(10)) {
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
     * @throws ArithmeticError If base is 0, 1, or if this number is 0.
     */
    public function log(Complex|int|float $base): Complex
    {
        // Make sure $base is Complex.
        $base = self::toComplex($base);

        // Check for invalid base values.
        if ($base->eq(0)) {
            throw new ArithmeticError("Logarithm base cannot be 0.");
        }
        if ($base->eq(1)) {
            throw new ArithmeticError("Logarithm base cannot be 1.");
        }

        // Check for natural logarithm.
        if ($base->eq(M_E)) {
            return $this->ln();
        }

        // Use built-in constants for log_2(e) and log_10(e).
        if ($this->eq(M_E)) {
            if ($base->eq(2)) {
                return new Complex(M_LOG2E);
            } elseif ($base->eq(10)) {
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
        if ($this->eq(0)) {
            return new Complex(1);
        } elseif ($this->eq(M_LN2)) {
            return new Complex(2);
        } elseif ($this->eq(1)) {
            return new Complex(M_E);
        } elseif ($this->eq(M_LNPI)) {
            return new Complex(M_PI);
        } elseif ($this->eq(M_LN10)) {
            return new Complex(10);
        }

        // Check for Euler's identity e^iπ = -1
        if ($this->eq(new Complex(0, M_PI))) {
            return new Complex(-1);
        }

        // Uses Euler's formula: e^(a + bi) = e^a * (cos(b) + i*sin(b))
        return self::fromPolar(exp($this->real), $this->imag);
    }

    /**
     * Raise this complex number to a power.
     * This is a multi-valued function but for simplicity only the principal value is returned. 
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
        if ($this->eq(0)) {
            // Check for complex exponent.
            if (!$other->isReal()) {
                throw new InvalidArgumentException("Cannot raise 0 to a complex number.");
            }

            // Check for negative real exponent.
            if ($other->isNegativeReal()) {
                throw new InvalidArgumentException("Cannot raise 0 to a negative real number.");
            }

            // Check for 0 exponent.
            if ($other->eq(0)) {
                // Although mathematically 0^0 is undefined, return 1 for consistency with pow(0, 0).
                // This is a common result in many programming languages.
                // (Principle of least astonishment.)
                // @see https://en.wikipedia.org/wiki/Zero_to_the_power_of_zero
                return new Complex(1);
            }

            // The exponent is a positive real number, and 0 raised to any positive real number is 0.
            return new Complex();
        }

        // Handle exponent = 0. Any non-zero number to power 0 is 1.
        if ($other->eq(0)) {
            return new Complex(1);
        }

        // Handle exponent = 1. Any number to power 1 is itself.
        if ($other->eq(1)) {
            return clone $this;
        }

        // Handle i^2 = -1.
        if ($this->eq(self::i()) && $other->eq(2)) {
            return new Complex(-1);
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
        if ($this->eq(0)) {
            return [new Complex()];
        }

        // Calculate the magnitude of the roots.
        $root_mag = pow($this->mag, 1.0 / $n);

        // Calculate all n roots.
        $roots = [];
        for ($k = 0; $k < $n; $k++) {
            $root_phase = ($this->phase + 2 * M_PI * $k) / $n;
            $roots[] = Complex::fromPolar($root_mag, $root_phase);
        }

        return $roots;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Comparison methods

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
     * Check if a complex number is a negative real number.
     * 
     * @return bool True if the complex number is a negative real number, otherwise false. 
     */
    public function isNegativeReal(): bool
    {
        return $this->isReal() && $this->real < 0;
    }

    /**
     * Check if a complex number is a positive real number.
     * 
     * @return bool True if the complex number is a positive real number, otherwise false. 
     */
    public function isPositiveReal(): bool
    {
        return $this->isReal() && $this->real > 0;
    }

    /**
     * Check if this complex number equals another.
     * 
     * @param Complex|int|float $other The real or complex number to compare with.
     * @param float $epsilon The tolerance for floating-point comparison.
     * @return bool True if the numbers are equal within the tolerance.
     */
    public function eq(Complex|int|float $other, float $epsilon = PHP_FLOAT_EPSILON): bool
    {
        // Make sure $other is Complex.
        $other = self::toComplex($other);

        // Compare real and imaginary parts.
        return abs($this->real - $other->real) < $epsilon &&
            abs($this->imag - $other->imag) < $epsilon;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Factory methods

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

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Stringable implementation

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
            } elseif ($this->imag == -1) {
                return '-i';
            } else {
                return $this->imag . 'i';
            }
        }

        // Construct the string for the a + bi or a - bi form.
        $op = $this->imag > 0 ? ' + ' : ' - ';
        $abs = abs($this->imag);
        $imag = $abs == 1 ? '' : $abs;
        return $this->real . $op . $imag . 'i';
    }
}
