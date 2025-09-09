<?php

declare(strict_types = 1);

namespace Math;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
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
        $this->assertTrue($a->equals($b), "Angles differ: {$a} vs {$b}");
    }

    /**
     * Test factoriesAndGettersRoundtrip scenario.
     *
     * @return void
     */
    #[Test]
    public function factoriesAndGettersRoundtrip(): void
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
    #[Test]
    public function dmsRoundtripAndCarry(): void
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
        $this->expectException(InvalidArgumentException::class);
        $x = $b->toDMS(3, 3);
    }

    /**
     * Test parsingCssUnitsAndDms scenario.
     *
     * @return void
     */
    #[Test]
    public function parsingCssUnitsAndDms(): void
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
    #[Test]
    public function parseRejectsBadInputs(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Angle::fromString('');
    }

    /**
     * Test wrapUnsignedAndSigned scenario.
     *
     * @return void
     */
    #[Test]
    public function wrapUnsignedAndSigned(): void
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
    #[Test]
    public function arithmeticAndCompare(): void
    {
        $a = Angle::fromDegrees(10);
        $b = Angle::fromDegrees(370);
        // 10° vs. 370° differ by 0° on a circle.
        $this->assertSame(0, $a->compare($b));

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
    #[Test]
    public function trigAndReciprocalsBehaviour(): void
    {
        $a = Angle::fromDegrees(60);
        $this->assertFloatEquals(sqrt(3) / 2, $a->sin());
        $this->assertFloatEquals(0.5, $a->cos());
        $this->assertFloatEquals(sqrt(3), $a->tan());

        // Reciprocals: check INF at singularities.

        // Tan(90°) = ∞.
        $t = Angle::fromDegrees(90);
        $this->assertTrue(is_infinite($t->tan()));

        // Csc(180°) = ∞.
        $b = Angle::fromDegrees(180);
        $this->assertTrue(is_infinite($b->csc()));

        // Sec(90°) = ∞.
        $c = Angle::fromDegrees(90);
        $this->assertTrue(is_infinite($c->sec())); // cos(90°)=0 ⇒ sec=∞

        // Cot(0) = ∞.
        $d = Angle::fromDegrees(0);
        $this->assertTrue(is_infinite($d->cot()));
    }

    /**
     * Test formatVariants scenario.
     *
     * @return void
     */
    #[Test]
    public function formatVariants(): void
    {
        $a = Angle::fromDegrees(12.5);
        $this->assertSame('0.2181661565rad', $a->format('rad', 10));
        $this->assertSame('12.50deg', $a->format('deg', 2));
        $this->assertSame('13.888888889grad', $a->format('grad', 9));
        $this->assertSame('0.0347222222turn', $a->format('turn', 10));

        // DMS via format.
        $this->assertSame("12° 30′ 0″", $a->format('dms', 0));

        // Invalid decimals value.
        $this->expectException(InvalidArgumentException::class);
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
    #[Test]
    public function randomRoundtripsRadiansDegreesGradiansTurns(): void
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
    #[Test]
    public function formatThenParseRoundtripVariousStyles(): void
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
                    $a->equals($b),
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
    #[Test]
    public function wrapBoundariesSignedAndUnsigned(): void
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
    #[Test]
    public function dmsExtremesAndOutOfRangeParts(): void
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
    #[Test]
    public function parsingWhitespaceAndCaseAndAsciiUnicodeSymbols(): void
    {
        $this->assertTrue(Angle::fromDegrees(12)->equals(Angle::fromString('12 DEG')));
        $this->assertTrue(Angle::fromTurns(0.25)->equals(Angle::fromString(' 0.25   turn ')));
        $this->assertTrue(Angle::fromRadians(M_PI)->equals(Angle::fromString(sprintf('%.12frad', M_PI))));

        // Unicode DMS.
        $this->assertTrue(Angle::fromDMS(12, 34, 56)->equals(Angle::fromString('12° 34′ 56″')));
        // ASCII DMS.
        $this->assertTrue(Angle::fromDMS(-12, -34, -56)->equals(Angle::fromString("-12°34'56\"")));

        // Invalid DMS format.
        $this->expectException(InvalidArgumentException::class);
        $a = Angle::fromString('-');
    }

    /**
     * Test reciprocalsNearSingularitiesWithEpsilonHandling scenario.
     *
     * @return void
     */
    #[Test]
    public function reciprocalsNearSingularitiesWithEpsilonHandling(): void
    {
        // Sec(90°) → ±INF.
        $this->assertTrue(is_infinite(Angle::fromDegrees(90)->sec()));
        // Csc(0°) → ±INF.
        $this->assertTrue(is_infinite(Angle::fromDegrees(0)->csc()));
        // Cot(0°) → ±INF.
        $this->assertTrue(is_infinite(Angle::fromDegrees(0)->cot()));

        // Tiny offsets either side keep the sign logic consistent.
        $this->assertGreaterThan(0, Angle::fromRadians(1e-14)->csc());
        $this->assertLessThan(0, Angle::fromRadians(-1e-14)->csc());
    }

    /**
     * Test tryParseSuccessAndFailure scenario.
     *
     * @return void
     */
    #[Test]
    public function tryParseSuccessAndFailure(): void
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
    #[Test]
    public function divisionByZero(): void
    {
        $a = Angle::fromDegrees(90);
        $this->expectException(InvalidArgumentException::class);
        $a->div(0.0);
    }

    /**
     * Test compare epsilon negative throws and delta sign behaviors.
     *
     * @return void
     */
    #[Test]
    public function compareWithEpsilonAndDelta(): void
    {
        $a = Angle::fromDegrees(10);
        $b = Angle::fromDegrees(20);

        // Delta is negative -> a < b.
        $this->assertSame(-1, $a->compare($b));

        // Delta is positive -> b > a.
        $this->assertSame(1, $b->compare($a));

        // Epsilon negative -> invalid argument.
        $this->expectException(InvalidArgumentException::class);
        $a->compare($b, -1e-9);
    }

    /**
     * Test wrapThis normalizes the internal angle.
     *
     * @return void
     */
    #[Test]
    public function wrapThisBehaviour(): void
    {
        $a = Angle::fromDegrees(370);
        $a->wrapThis(false);
        $this->assertFloatEquals(10.0, $a->toDegrees());

        $b = Angle::fromDegrees(-190);
        $b->wrapThis(true);
        $this->assertFloatEquals(170.0, $b->toDegrees());
    }

    /**
     * Test wrapGradians normalizes values for signed and unsigned ranges.
     *
     * @return void
     */
    #[Test]
    public function wrapGradiansBehaviour(): void
    {
        $this->assertFloatEquals(50.0, Angle::wrapGradians(450.0, false));
        $this->assertFloatEquals(190.0, Angle::wrapGradians(-210.0, true));
    }

    /**
     * Test wrapDegrees normalizes values for signed and unsigned ranges.
     *
     * @return void
     */
    #[Test]
    public function wrapDegreesBehaviour(): void
    {
        $this->assertFloatEquals(50.0, Angle::wrapDegrees(410.0, false));
        $this->assertFloatEquals(150.0, Angle::wrapDegrees(-210.0, true));
    }

    /**
     * Test hyperbolic trig functions match PHP's implementations.
     *
     * @return void
     */
    #[Test]
    public function hyperbolicTrigFunctions(): void
    {
        $x = 0.5;
        $a = Angle::fromRadians($x);

        $this->assertFloatEquals(sinh($x), $a->sinh());
        $this->assertFloatEquals(cosh($x), $a->cosh());
        $this->assertFloatEquals(tanh($x), $a->tanh());
        $this->assertFloatEquals(1 / sinh($x), $a->csch());
        $this->assertFloatEquals(1 / cosh($x), $a->sech());
        $this->assertFloatEquals(1 / tanh($x), $a->coth());
    }
}
