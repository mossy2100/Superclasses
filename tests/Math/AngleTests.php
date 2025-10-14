<?php

declare(strict_types = 1);

namespace Superclasses\Tests\Math;

use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Superclasses\Math\Angle;

#[CoversClass(Angle::class)]
final class AngleTests extends TestCase
{
    private function assertFloatEquals(float $expected, float $actual, float $delta = Angle::RAD_EPSILON): void
    {
        $this->assertEqualsWithDelta($expected, $actual, $delta);
    }

    private function assertAngleEquals(Angle $a, Angle $b): void
    {
        $this->assertTrue($a->eq($b), "Angles differ: {$a} vs {$b}");
    }

    /**
     * Test factoriesAndGettersRoundtrip scenario.
     *
     * @return void
     */
    public function testFactoriesAndGettersRoundtrip(): void
    {
        $a = Angle::fromDegrees(180.0);
        $this->assertFloatEquals(M_PI, $a->toRadians());
        $this->assertFloatEquals(180.0, $a->toDegrees());
        $this->assertFloatEquals(200.0, $a->toGradians());
        $this->assertFloatEquals(0.5, $a->toTurns());
    }

    /**
     * Test dmsRoundtripAndCarry scenario.
     *
     * @return void
     */
    public function testDmsRoundtripAndCarry(): void
    {
        $a = Angle::fromDMS(12, 34, 56);
        [$d, $m, $s] = $a->toDMS(2, 6);
        $this->assertFloatEquals(12.0, $d);
        $this->assertFloatEquals(34.0, $m);
        $this->assertFloatEquals(56.0, $s);

        // Force carry at seconds -> minutes and minutes -> degrees.
        $b = Angle::fromDegrees(29.999999999);
        [$d2, $m2, $s2] = $b->toDMS(2, 3);
        $this->assertFloatEquals(30.0, $d2);
        $this->assertFloatEquals(0.0, $m2);
        $this->assertFloatEquals(0.0, $s2);

        // Force carry at minutes -> degrees.
        $b = Angle::fromDegrees(29.999999999);
        [$d3, $m3] = $b->toDMS(1, 3);
        $this->assertFloatEquals(30.0, $d3);
        $this->assertFloatEquals(0.0, $m3);

        // Test invalid smallest unit index.
        $this->expectException(DomainException::class);
        $x = $b->toDMS(3, 3);
    }

    /**
     * Test parsingCssUnitsAndDms scenario.
     *
     * @return void
     */
    public function testParsingCssUnitsAndDms(): void
    {
        $this->assertAngleEquals(Angle::fromDegrees(12), Angle::fromString('12deg'));
        $this->assertAngleEquals(Angle::fromDegrees(12), Angle::fromString('12 DEG'));
        $this->assertAngleEquals(Angle::fromTurns(0.5), Angle::fromString('0.5 turn'));
        $this->assertAngleEquals(Angle::fromRadians(M_PI), Angle::fromString(M_PI . 'rad'));

        // Unicode symbols.
        $this->assertAngleEquals(Angle::fromDMS(12, 34, 56), Angle::fromString('12° 34′ 56″'));
        // ASCII fallback.
        $this->assertAngleEquals(Angle::fromDMS(-12, -34, -56), Angle::fromString("-12°34'56\""));
    }

    /**
     * Test parseRejectsBadInputs scenario.
     *
     * @return void
     */
    public function testParseRejectsBadInputs(): void
    {
        $this->expectException(DomainException::class);
        Angle::fromString('');
    }

    /**
     * Test wrapUnsignedAndSigned scenario.
     *
     * @return void
     */
    public function testWrapUnsignedAndSigned(): void
    {
        $a = Angle::fromRadians(2 * M_PI)->wrap(); // pure
        $this->assertFloatEquals(0.0, $a->toRadians());

        $b = Angle::fromRadians(M_PI)->wrap(true);
        // Signed range is [-π, π): π maps to -π.
        $this->assertFloatEquals(-M_PI, $b->toRadians());
    }

    /**
     * Test arithmeticAndCompare scenario.
     *
     * @return void
     */
    public function testArithmeticAndCompare(): void
    {
        $a = Angle::fromDegrees(10);
        $b = Angle::fromDegrees(370);
        // 10° vs. 370° differ by 0° on a circle.
        $this->assertSame(0, $a->cmp($b));

        $sum = $a->add(Angle::fromDegrees(20));
        $this->assertFloatEquals(30.0, $sum->toDegrees());

        $diff = $a->sub(Angle::fromDegrees(40));
        $this->assertFloatEquals(-30.0, $diff->toDegrees());

        $scaled = $a->mul(3)->div(2);
        $this->assertFloatEquals(15.0, $scaled->toDegrees());
    }

    /**
     * Test trigAndReciprocalsBehaviour scenario.
     *
     * @return void
     */
    public function testTrigAndReciprocalsBehaviour(): void
    {
        $a = Angle::fromDegrees(60);
        $this->assertFloatEquals(sqrt(3) / 2, $a->sin());
        $this->assertFloatEquals(0.5, $a->cos());
        $this->assertFloatEquals(sqrt(3), $a->tan());

        // Reciprocals: check INF at singularities.

        // Tan(90°) = ∞.
        $t = Angle::fromDegrees(90);
        $this->assertTrue(is_infinite($t->tan()));
    }

    /**
     * Test formatVariants scenario.
     *
     * @return void
     */
    public function testFormatVariants(): void
    {
        $a = Angle::fromDegrees(12.5);
        $this->assertSame('0.2181661565rad', $a->format('rad', 10));
        $this->assertSame('12.50deg', $a->format('deg', 2));
        $this->assertSame('13.888888889grad', $a->format('grad', 9));
        $this->assertSame('0.0347222222turn', $a->format('turn', 10));

        // DMS via format.
        $this->assertSame("12° 30′ 0″", $a->format('dms', 0));

        // Invalid decimals value.
        $this->expectException(DomainException::class);
        $a->format('rad', -1);
    }

    protected function setUp(): void
    {
        // Deterministic randomness.
        mt_srand(0xC0FFEE);
    }

    private function randFloat(float $min, float $max): float
    {
        // Uniform float in [min, max).
        return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
    }

    /**
     * Test randomRoundtripsRadiansDegreesGradiansTurns scenario.
     *
     * @return void
     */
    public function testRandomRoundtripsRadiansDegreesGradiansTurns(): void
    {
        for ($i = 0; $i < 500; $i++) {
            // Span a large range, including huge magnitudes.
            $rad = $this->randFloat(-1e6, 1e6);
            $a   = Angle::fromRadians($rad);

            // ToX / fromX.
            $this->assertFloatEquals($rad, Angle::fromRadians($a->toRadians())->toRadians());

            $deg = $a->toDegrees();
            $this->assertFloatEquals($a->toRadians(), Angle::fromDegrees($deg)->toRadians());

            $grad = $a->toGradians();
            $this->assertFloatEquals($a->toRadians(), Angle::fromGradians($grad)->toRadians());

            $turn = $a->toTurns();
            $this->assertFloatEquals($a->toRadians(), Angle::fromTurns($turn)->toRadians());
        }
    }

    /**
     * Test formatThenParseRoundtripVariousStyles scenario.
     *
     * @return void
     */
    public function testFormatThenParseRoundtripVariousStyles(): void
    {
        $styles = ['rad', 'deg', 'grad', 'turn', 'd', 'dm', 'dms'];

        for ($i = 0; $i < 200; $i++) {
            $rad = $this->randFloat(-1000.0, 1000.0);
            $a   = Angle::fromRadians($rad);

            foreach ($styles as $style) {
                // Use max float precision to ensure correct round trip conversion.
                $s = $a->format($style, 17);
                $b = Angle::fromString($s);

                $this->assertTrue(
                    $a->eq($b),
                    "Format/fromString mismatch for style '{$style}': {$s} → {$b} vs {$a}"
                );
            }
        }
    }

    /**
     * Test wrapBoundariesSignedAndUnsigned scenario.
     *
     * @return void
     */
    public function testWrapBoundariesSignedAndUnsigned(): void
    {
        $twoPi = 2 * M_PI;

        // Unsigned [0, τ).
        $this->assertFloatEquals(0.0, Angle::wrapRadians(0.0, false));
        $this->assertFloatEquals(0.0, Angle::wrapRadians($twoPi, false));
        $this->assertFloatEquals(0.0, Angle::wrapRadians(-$twoPi, false));
        $this->assertFloatEquals(M_PI, Angle::wrapRadians(-M_PI, false));

        // Signed [-π, π).
        $this->assertFloatEquals(-M_PI, Angle::wrapRadians(M_PI, true));  // right edge maps to -π
        $this->assertFloatEquals(-M_PI, Angle::wrapRadians(-M_PI, true));
        $this->assertFloatEquals(0.0, Angle::wrapRadians($twoPi, true));
        $this->assertFloatEquals(0.0, Angle::wrapRadians(-$twoPi, true));

        // Instance versions stay pure vs. mutating pair.
        $a = Angle::fromRadians($twoPi)->wrap();
        $this->assertFloatEquals(0.0, $a->toRadians());
        $b = Angle::fromRadians(M_PI)->wrap(true);
        $this->assertFloatEquals(-M_PI, $b->toRadians());
    }

    /**
     * Test dmsExtremesAndOutOfRangeParts scenario.
     *
     * @return void
     */
    public function testDmsExtremesAndOutOfRangeParts(): void
    {
        // Minutes/seconds beyond their usual ranges should still compute correctly.
        $a = Angle::fromDMS(10, 120, 120); // 10° + 2° + 0.033...° = 12.033...
        $this->assertFloatEquals(12.0333333333, $a->toDegrees(), 1e-9);

        // Mixed signs as documented (caller responsibility).
        $b = Angle::fromDMS(-12, -90, 30); // -12 - 1.5 + 0.008333... = -13.491666...
        $this->assertFloatEquals(-13.4916666667, $b->toDegrees(), 1e-9);

        // Round-trip to DMS with carry after rounding.
        $c = Angle::fromDegrees(29.999999999);
        [$d, $m, $s] = $c->toDMS(2, 3);
        $this->assertFloatEquals(30.0, $d);
        $this->assertFloatEquals(0.0, $m);
        $this->assertFloatEquals(0.0, $s);
    }

    /**
     * Test parsingWhitespaceAndCaseAndAsciiUnicodeSymbols scenario.
     *
     * @return void
     */
    public function testParsingWhitespaceAndCaseAndAsciiUnicodeSymbols(): void
    {
        $this->assertTrue(Angle::fromDegrees(12)->eq(Angle::fromString('12 DEG')));
        $this->assertTrue(Angle::fromTurns(0.25)->eq(Angle::fromString(' 0.25   turn ')));
        $this->assertTrue(Angle::fromRadians(M_PI)->eq(Angle::fromString(sprintf('%.12frad', M_PI))));

        // Unicode DMS.
        $this->assertTrue(Angle::fromDMS(12, 34, 56)->eq(Angle::fromString('12° 34′ 56″')));
        // ASCII DMS.
        $this->assertTrue(Angle::fromDMS(-12, -34, -56)->eq(Angle::fromString("-12°34'56\"")));

        // Invalid DMS format.
        $this->expectException(DomainException::class);
        $a = Angle::fromString('-');
    }

    /**
     * Test tryParseSuccessAndFailure scenario.
     *
     * @return void
     */
    public function testTryParseSuccessAndFailure(): void
    {
        $ok = Angle::tryParse('12deg', $a);
        $this->assertTrue($ok);
        $this->assertInstanceOf(Angle::class, $a);

        $bad = Angle::tryParse('not an angle', $b);
        $this->assertFalse($bad);
        $this->assertNull($b);
    }

    /**
     * Test division by zero throws DivisionByZeroError.
     *
     * @return void
     */
    public function testDivisionByZero(): void
    {
        $a = Angle::fromDegrees(90);
        $this->expectException(DomainException::class);
        $a->div(0.0);
    }

    /**
     * Test compare epsilon negative throws and delta sign behaviors.
     *
     * @return void
     */
    public function testCompareWithEpsilonAndDelta(): void
    {
        $a = Angle::fromDegrees(10);
        $b = Angle::fromDegrees(20);

        // Delta is negative -> a < b.
        $this->assertSame(-1, $a->cmp($b));

        // Delta is positive -> b > a.
        $this->assertSame(1, $b->cmp($a));

        // Epsilon negative -> invalid argument.
        $this->expectException(DomainException::class);
        $a->cmp($b, -1e-9);
    }

    /**
     * Test wrapGradians normalizes values for signed and unsigned ranges.
     *
     * @return void
     */
    public function testWrapGradiansBehaviour(): void
    {
        $this->assertFloatEquals(50.0, Angle::wrapGradians(450.0, false));
        $this->assertFloatEquals(190.0, Angle::wrapGradians(-210.0, true));
    }

    /**
     * Test wrapDegrees normalizes values for signed and unsigned ranges.
     *
     * @return void
     */
    public function testWrapDegreesBehaviour(): void
    {
        $this->assertFloatEquals(50.0, Angle::wrapDegrees(410.0, false));
        $this->assertFloatEquals(150.0, Angle::wrapDegrees(-210.0, true));
    }

    /**
     * Test hyperbolic trig functions match PHP's implementations.
     *
     * @return void
     */
    public function testHyperbolicTrigFunctions(): void
    {
        $x = 0.5;
        $a = Angle::fromRadians($x);

        $this->assertFloatEquals(sinh($x), $a->sinh());
        $this->assertFloatEquals(cosh($x), $a->cosh());
        $this->assertFloatEquals(tanh($x), $a->tanh());
    }
}