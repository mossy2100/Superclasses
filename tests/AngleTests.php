<?php

declare(strict_types=1);

namespace Tests;

use InvalidArgumentException;
use Superclasses\Angle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Angle::class)]
final class AngleTests extends TestCase
{
    private const float DELTA = 1e-9;

    private function assertFloatEquals(float $expected, float $actual, float $delta = self::DELTA): void
    {
        $this->assertEqualsWithDelta($expected, $actual, $delta);
    }

    private function assertAngleEquals(Angle $a, Angle $b, float $eps = Angle::RAD_EPSILON): void
    {
        $this->assertTrue($a->eq($b, $eps), "Angles differ: {$a} vs {$b}");
    }

    /** @test */
    public function factories_and_getters_roundtrip(): void
    {
        $a = Angle::fromDegrees(180.0);
        $this->assertFloatEquals(M_PI, $a->toRadians());
        $this->assertFloatEquals(180.0, $a->toDegrees());
        $this->assertFloatEquals(200.0, $a->toGradians());
        $this->assertFloatEquals(0.5, $a->toTurns());
    }

    /** @test */
    public function dms_roundtrip_and_carry(): void
    {
        $a = Angle::fromDMS(12, 34, 56);
        [$d, $m, $s] = $a->toDMS(2, 6);
        $this->assertFloatEquals(12.0, $d);
        $this->assertFloatEquals(34.0, $m);
        $this->assertFloatEquals(56.0, $s);

        // Force carry at seconds -> minutes and minutes -> degrees
        $b = Angle::fromDegrees(29.999999999);
        [$d2, $m2, $s2] = $b->toDMS(2, 3);
        $this->assertFloatEquals(30.0, $d2);
        $this->assertFloatEquals(0.0, $m2);
        $this->assertFloatEquals(0.0, $s2);
    }

    /** @test */
    public function parsing_css_units_and_dms(): void
    {
        $this->assertAngleEquals(Angle::fromDegrees(12), Angle::fromString('12deg'));
        $this->assertAngleEquals(Angle::fromDegrees(12), Angle::fromString('12 DEG'));
        $this->assertAngleEquals(Angle::fromTurns(0.5), Angle::fromString('0.5 turn'));
        $this->assertAngleEquals(Angle::fromRadians(M_PI), Angle::fromString(M_PI . 'rad'));

        // Unicode symbols
        $this->assertAngleEquals(Angle::fromDMS(12,34,56), Angle::fromString('12° 34′ 56″'));
        // ASCII fallback
        $this->assertAngleEquals(Angle::fromDMS(-12, -34, -56), Angle::fromString("-12°34'56\""));
    }

    /** @test */
    public function parse_rejects_bad_inputs(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Angle::fromString('');
    }

    /** @test */
    public function wrap_unsigned_and_signed(): void
    {
        $a = Angle::fromRadians(2 * M_PI)->wrap(); // pure
        $this->assertFloatEquals(0.0, $a->toRadians());

        $b = Angle::fromRadians(M_PI)->wrap(true);
        // signed range is [-π, π): π maps to -π
        $this->assertFloatEquals(-M_PI, $b->toRadians());
    }

    /** @test */
    public function arithmetic_and_compare(): void
    {
        $a = Angle::fromDegrees(10);
        $b = Angle::fromDegrees(370);
        // 10° vs 370° differ by 0° on a circle
        $this->assertSame(0, $a->compare($b));

        $sum = $a->add(Angle::fromDegrees(20));
        $this->assertFloatEquals(30.0, $sum->toDegrees());

        $diff = $a->sub(Angle::fromDegrees(40));
        $this->assertFloatEquals(-30.0, $diff->toDegrees());

        $scaled = $a->mul(3)->div(2);
        $this->assertFloatEquals(15.0, $scaled->toDegrees());
    }

    /** @test */
    public function trig_and_reciprocals_behaviour(): void
    {
        $a = Angle::fromDegrees(60);
        $this->assertFloatEquals(sqrt(3)/2, $a->sin());
        $this->assertFloatEquals(0.5, $a->cos());
        $this->assertFloatEquals(sqrt(3), $a->tan());

        // Reciprocals: check INF at singularities.

        // csc(180°) = ∞
        $b = Angle::fromDegrees(180);
        $this->assertTrue(is_infinite($b->csc()));

        // sec(90°) = ∞
        $c = Angle::fromDegrees(90);
        $this->assertTrue(is_infinite($c->sec())); // cos(90°)=0 ⇒ sec=∞

        // cot(0) = ∞
        $d = Angle::fromDegrees(0);
        $this->assertTrue(is_infinite($d->cot()));
    }

    /** @test */
    public function format_variants(): void
    {
        $a = Angle::fromDegrees(12.5);
        $this->assertSame('0.2181661565rad', $a->format('rad', 10));
        $this->assertSame('12.50deg', $a->format('deg', 2));
        $this->assertSame('13.888888889grad', $a->format('grad', 9));
        $this->assertSame('0.0347222222turn', $a->format('turn', 10));

        // DMS via format
        $this->assertSame("12° 30′ 0″", $a->format('dms', 0));
    }

    protected function setUp(): void
    {
        // Deterministic randomness
        mt_srand(0xC0FFEE);
    }

    private function randFloat(float $min, float $max): float
    {
        // Uniform float in [min, max)
        return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
    }

    /** @test */
    public function random_roundtrips_radians_degrees_gradians_turns(): void
    {
        for ($i = 0; $i < 500; $i++) {
            // Span a large range, including huge magnitudes
            $rad = $this->randFloat(-1e6, 1e6);
            $a = Angle::fromRadians($rad);

            // toX / fromX
            $this->assertFloatEquals($rad, Angle::fromRadians($a->toRadians())->toRadians());

            $deg = $a->toDegrees();
            $this->assertFloatEquals($a->toRadians(), Angle::fromDegrees($deg)->toRadians());

            $grad = $a->toGradians();
            $this->assertFloatEquals($a->toRadians(), Angle::fromGradians($grad)->toRadians());

            $turn = $a->toTurns();
            $this->assertFloatEquals($a->toRadians(), Angle::fromTurns($turn)->toRadians());
        }
    }

    /** @test */
    public function format_then_parse_roundtrip_various_styles(): void
    {
        $styles = ['rad','deg','grad','turn','d','dm','dms'];

        for ($i = 0; $i < 200; $i++) {
            $rad = $this->randFloat(-1000.0, 1000.0);
            $a = Angle::fromRadians($rad);

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

    /** @test */
    public function wrap_boundaries_signed_and_unsigned(): void
    {
        $twoPi = 2 * M_PI;

        // Unsigned [0, τ)
        $this->assertFloatEquals(0.0, Angle::wrapRadians(0.0, false));
        $this->assertFloatEquals(0.0, Angle::wrapRadians($twoPi, false));
        $this->assertFloatEquals(0.0, Angle::wrapRadians(-$twoPi, false));
        $this->assertFloatEquals(M_PI, Angle::wrapRadians(-M_PI, false));

        // Signed [-π, π)
        $this->assertFloatEquals(-M_PI, Angle::wrapRadians(M_PI, true));  // right edge maps to -π
        $this->assertFloatEquals(-M_PI, Angle::wrapRadians(-M_PI, true));
        $this->assertFloatEquals(0.0, Angle::wrapRadians($twoPi, true));
        $this->assertFloatEquals(0.0, Angle::wrapRadians(-$twoPi, true));

        // Instance versions stay pure vs mutating pair
        $a = Angle::fromRadians($twoPi)->wrap();
        $this->assertFloatEquals(0.0, $a->toRadians());
        $b = Angle::fromRadians(M_PI)->wrap(true);
        $this->assertFloatEquals(-M_PI, $b->toRadians());
    }

    /** @test */
    public function dms_extremes_and_out_of_range_parts(): void
    {
        // Minutes/seconds beyond their usual ranges should still compute correctly
        $a = Angle::fromDMS(10, 120, 120); // 10° + 2° + 0.033...° = 12.033...
        $this->assertFloatEquals(12.0333333333, $a->toDegrees(), 1e-9);

        // Mixed signs as documented (caller responsibility)
        $b = Angle::fromDMS(-12, -90, 30); // -12 - 1.5 + 0.008333... = -13.491666...
        $this->assertFloatEquals(-13.4916666667, $b->toDegrees(), 1e-9);

        // Round-trip to DMS with carry after rounding
        $c = Angle::fromDegrees(29.999999999);
        [$d, $m, $s] = $c->toDMS(2, 3);
        $this->assertFloatEquals(30.0, $d);
        $this->assertFloatEquals(0.0, $m);
        $this->assertFloatEquals(0.0, $s);
    }

    /** @test */
    public function parsing_whitespace_and_case_and_ascii_unicode_symbols(): void
    {
        $this->assertTrue(Angle::fromDegrees(12)->eq(Angle::fromString('12 DEG')));
        $this->assertTrue(Angle::fromTurns(0.25)->eq(Angle::fromString(' 0.25   turn ')));
        $this->assertTrue(Angle::fromRadians(M_PI)->eq(Angle::fromString(sprintf('%.12frad', M_PI))));

        // Unicode DMS
        $this->assertTrue(Angle::fromDMS(12, 34, 56)->eq(Angle::fromString('12° 34′ 56″')));
        // ASCII DMS
        $this->assertTrue(Angle::fromDMS(-12, -34, -56)->eq(Angle::fromString("-12°34'56\"")));
    }

    /** @test */
    public function reciprocals_near_singularities_with_epsilon_handling(): void
    {
        // sec(90°) → ±INF
        $this->assertTrue(is_infinite(Angle::fromDegrees(90)->sec()));
        // csc(0°) → ±INF
        $this->assertTrue(is_infinite(Angle::fromDegrees(0)->csc()));
        // cot(0°) → ±INF
        $this->assertTrue(is_infinite(Angle::fromDegrees(0)->cot()));

        // Tiny offsets either side keep the sign logic consistent
        $this->assertGreaterThan(0, Angle::fromRadians( 1e-14)->csc());
        $this->assertLessThan(0,    Angle::fromRadians(-1e-14)->csc());
    }

    /** @test */
    public function tryparse_success_and_failure(): void
    {
        $ok = Angle::tryParse('12deg', $a);
        $this->assertTrue($ok);
        $this->assertInstanceOf(Angle::class, $a);

        $bad = Angle::tryParse('not an angle', $b);
        $this->assertFalse($bad);
        $this->assertNull($b);
    }
}
