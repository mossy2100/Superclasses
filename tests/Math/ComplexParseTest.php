<?php

declare(strict_types = 1);

namespace Superclasses\Tests\Math;

use PHPUnit\Framework\TestCase;
use Superclasses\Math\Complex;
use InvalidArgumentException;

class ComplexParseTest extends TestCase
{
    /**
     * Test parsing pure real numbers
     */
    public function testParseRealNumbers(): void
    {
        $this->assertEquals(new Complex(5, 0), Complex::parse('5'));
        $this->assertEquals(new Complex(-3.14, 0), Complex::parse('-3.14'));
        $this->assertEquals(new Complex(0, 0), Complex::parse('0'));
        $this->assertEquals(new Complex(123.0, 0), Complex::parse('123.'));
        $this->assertEquals(new Complex(0.45, 0), Complex::parse('.45'));
        $this->assertEquals(new Complex(1.23e4, 0), Complex::parse('1.23e4'));
        $this->assertEquals(new Complex(-2.5e-3, 0), Complex::parse('-2.5e-3'));
    }

    /**
     * Test parsing pure imaginary numbers
     */
    public function testParsePureImaginary(): void
    {
        // Basic imaginary units
        $this->assertEquals(new Complex(0, 1), Complex::parse('i'));
        $this->assertEquals(new Complex(0, 1), Complex::parse('j'));
        $this->assertEquals(new Complex(0, 1), Complex::parse('I'));
        $this->assertEquals(new Complex(0, 1), Complex::parse('J'));

        // Negative imaginary units
        $this->assertEquals(new Complex(0, -1), Complex::parse('-i'));
        $this->assertEquals(new Complex(0, -1), Complex::parse('-j'));
        $this->assertEquals(new Complex(0, -1), Complex::parse('-I'));
        $this->assertEquals(new Complex(0, -1), Complex::parse('-J'));

        // Imaginary with coefficients
        $this->assertEquals(new Complex(0, 3), Complex::parse('3i'));
        $this->assertEquals(new Complex(0, -2.5), Complex::parse('-2.5j'));
        $this->assertEquals(new Complex(0, 0.75), Complex::parse('0.75I'));
        $this->assertEquals(new Complex(0, 1.5e2), Complex::parse('1.5e2J'));
    }

    /**
     * Test parsing complex numbers (real + imaginary)
     */
    public function testParseComplexRealFirst(): void
    {
        // Standard format: a+bi
        $this->assertEquals(new Complex(3, 4), Complex::parse('3+4i'));
        $this->assertEquals(new Complex(5, -2), Complex::parse('5-2j'));
        $this->assertEquals(new Complex(-1, 1), Complex::parse('-1+i'));
        $this->assertEquals(new Complex(2.5, -3.7), Complex::parse('2.5-3.7I'));

        // With decimals and scientific notation
        $this->assertEquals(new Complex(1.23, 4.56), Complex::parse('1.23+4.56i'));
        $this->assertEquals(new Complex(-0.5, 2.5e-1), Complex::parse('-0.5+2.5e-1j'));
        $this->assertEquals(new Complex(123.0, -1), Complex::parse('123.-I'));
    }

    /**
     * Test parsing complex numbers (imaginary + real)
     */
    public function testParseComplexImagFirst(): void
    {
        // Standard format: bi+a
        $this->assertEquals(new Complex(3, 4), Complex::parse('4i+3'));
        $this->assertEquals(new Complex(5, -2), Complex::parse('-2j+5'));
        $this->assertEquals(new Complex(-1, 1), Complex::parse('i-1'));
        $this->assertEquals(new Complex(2.5, -3.7), Complex::parse('-3.7I+2.5'));

        // With decimals and scientific notation
        $this->assertEquals(new Complex(1.23, 4.56), Complex::parse('4.56i+1.23'));
        $this->assertEquals(new Complex(-0.5, 2.5e-1), Complex::parse('2.5e-1j-0.5'));
    }

    /**
     * Test parsing with whitespace (should be stripped)
     */
    public function testParseWithWhitespace(): void
    {
        $this->assertEquals(new Complex(3, 4), Complex::parse(' 3 + 4i '));
        $this->assertEquals(new Complex(5, -2), Complex::parse('5 - 2j'));
        $this->assertEquals(new Complex(-1, 1), Complex::parse(' -1 + i'));
        $this->assertEquals(new Complex(3, 4), Complex::parse('4i + 3'));
        $this->assertEquals(new Complex(0, 1), Complex::parse(' i '));
        $this->assertEquals(new Complex(5, 0), Complex::parse(' 5 '));
    }

    /**
     * Test edge cases with coefficients
     */
    public function testParseCoefficientEdgeCases(): void
    {
        // Explicit positive signs
        $this->assertEquals(new Complex(0, 1), Complex::parse('+i'));

        // Zero coefficients
        $this->assertEquals(new Complex(0, 0), Complex::parse('0i'));
        $this->assertEquals(new Complex(0, 0), Complex::parse('0+0i'));

        // Trailing decimal points
        $this->assertEquals(new Complex(3, 4), Complex::parse('3.+4.i'));
    }

    /**
     * Test error cases that should throw InvalidArgumentException
     */
    public function testParseInvalidInput(): void
    {
        $invalid_inputs = [
            '',           // Empty string
            'abc',        // Random text
            '3+',         // Incomplete expression
            '++i',        // Double signs
            '3+4',        // Missing imaginary unit
            'i+',         // Incomplete
            '3+4k',       // Wrong imaginary unit
            '3.4.5',      // Multiple decimal points
            '3e',         // Incomplete scientific notation
            '3ee4',       // Double e
        ];

        foreach ($invalid_inputs as $input) {
            $this->expectException(InvalidArgumentException::class);
            Complex::parse($input);
        }
    }

    /**
     * Test parsing returns new Complex instances
     */
    public function testParseReturnsNewInstances(): void
    {
        $c1 = Complex::parse('3+4i');
        $c2 = Complex::parse('3+4i');

        $this->assertEquals($c1, $c2);
        $this->assertNotSame($c1, $c2); // Different object instances
    }

    /**
     * Test scientific notation edge cases
     */
    public function testParseScientificNotation(): void
    {
        $this->assertEquals(new Complex(1.5e10, 0), Complex::parse('1.5e10'));
        $this->assertEquals(new Complex(0, -2.3e-5), Complex::parse('-2.3e-5i'));
        $this->assertEquals(new Complex(1e5, 2e-3), Complex::parse('1e5+2e-3j'));
        $this->assertEquals(new Complex(-1.5, 3.2e4), Complex::parse('3.2e4i-1.5'));
    }

    /**
     * Data provider for comprehensive format testing
     *
     * @return array
     */
    public static function complexNumberProvider(): array
    {
        return [
            // [input_string, expected_real, expected_imag]
            ['0', 0, 0],
            ['5', 5, 0],
            ['-3.14', -3.14, 0],
            ['i', 0, 1],
            ['-i', 0, -1],
            ['3i', 0, 3],
            ['-2.5j', 0, -2.5],
            ['3+4i', 3, 4],
            ['5-2j', 5, -2],
            ['-1+i', -1, 1],
            ['4i+3', 3, 4],
            ['-2j+5', 5, -2],
            ['i-1', -1, 1],
            [' 3 + 4i ', 3, 4],
            ['1.5e2+3.2e-1i', 150, 0.32],
        ];
    }

    /**
     * @dataProvider complexNumberProvider
     */
    public function testParseComprehensive(string $input, float $expected_real, float $expected_imag): void
    {
        $result = Complex::parse($input);
        $expected = new Complex($expected_real, $expected_imag);

        $this->assertEquals($expected, $result);
        $this->assertEqualsWithDelta($expected_real, $result->real, 1e-10);
        $this->assertEqualsWithDelta($expected_imag, $result->imag, 1e-10);
    }
}
