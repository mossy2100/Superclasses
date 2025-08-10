<?php

declare(strict_types=1);

namespace Superclasses;

use InvalidArgumentException;

class Angles
{
    // -----------------------------
    // Constants
    // -----------------------------

    // Degrees, arcminutes, arcseconds
    public const DEGREES_PER_CIRCLE       = 360;
    public const DEGREES_PER_SEMICIRCLE   = 180;
    public const DEGREES_PER_QUADRANT     = 90;
    public const ARCMINUTES_PER_DEGREE    = 60;
    public const ARCSECONDS_PER_ARCMINUTE = 60;
    public const ARCSECONDS_PER_DEGREE    = 3600;

    // Radians, tau
    public const TAU                     = M_PI * 2; // 2π
    public const RADIANS_PER_CIRCLE      = self::TAU;
    public const RADIANS_PER_SEMICIRCLE  = M_PI;
    public const RADIANS_PER_QUADRANT    = M_PI / 2;
    public const DEGREES_PER_RADIAN      = 180 / M_PI;
    public const ARCMINUTES_PER_RADIAN   = 10800 / M_PI;
    public const ARCSECONDS_PER_RADIAN   = 648000 / M_PI;

    // Gradians
    public const GRADIANS_PER_CIRCLE     = 400;
    public const GRADIANS_PER_SEMICIRCLE = 200;
    public const GRADIANS_PER_QUADRANT   = 100;
    public const GRADIANS_PER_RADIAN     = 200 / M_PI;
    public const DEGREES_PER_GRADIAN     = 0.9;

    private function __construct()
    {
    } // static-only

    // -----------------------------
    // Wrapping / normalization
    // -----------------------------

    /**
     * Normalize an angle in radians into the range [0, TAU).
     */
    public static function wrapRadians(float $rad): float
    {
        // Use fmod to avoid overflow, then ensure positive range.
        $r = fmod($rad, self::RADIANS_PER_CIRCLE);
        if ($r < 0) {
            $r += self::RADIANS_PER_CIRCLE;
        }
        return $r;
    }

    /**
     * Normalize an angle in degrees into the range [0, 360).
     */
    public static function wrapDegrees(float $deg): float
    {
        $d = fmod($deg, self::DEGREES_PER_CIRCLE);
        if ($d < 0) {
            $d += self::DEGREES_PER_CIRCLE;
        }
        return $d;
    }

    /**
     * Normalize an angle in gradians into the range [0, 400).
     */
    public static function wrapGradians(float $grad): float
    {
        $g = fmod($grad, self::GRADIANS_PER_CIRCLE);
        if ($g < 0) {
            $g += self::GRADIANS_PER_CIRCLE;
        }
        return $g;
    }

    // -----------------------------
    // Conversion methods
    // -----------------------------

    /**
     * Remember, converting between degrees and radians is covered by built-in functions:
     * deg2rad()
     * @see https://www.php.net/manual/en/function.deg2rad.php
     * rad2deg()
     * @see https://www.php.net/manual/en/function.rad2deg.php
     */

    // ---- Converting between degrees and gradians.

    public static function degToGrad(float $deg): float
    {
        return $deg / self::DEGREES_PER_GRADIAN;
    }

    public static function gradToDeg(float $grad): float
    {
        return $grad * self::DEGREES_PER_GRADIAN;
    }

    // ---- Converting between radians and gradians.

    public static function radToGrad(float $rad): float
    {
        return $rad * self::GRADIANS_PER_RADIAN;
    }

    public static function gradToRad(float $grad): float
    {
        return $grad / self::GRADIANS_PER_RADIAN;
    }

    // -----------------------------
    // Trig helpers
    // -----------------------------

    public static function sin2(float $rad): float
    {
        $s = sin($rad);
        return $s * $s;
    }

    public static function cos2(float $rad): float
    {
        $c = cos($rad);
        return $c * $c;
    }

    public static function tan2(float $rad): float
    {
        $t = tan($rad);
        return $t * $t;
    }

    public static function sinDeg(float $deg): float
    {
        return sin(deg2rad($deg));
    }

    public static function cosDeg(float $deg): float
    {
        return cos(deg2rad($deg));
    }

    public static function tanDeg(float $deg): float
    {
        return tan(deg2rad($deg));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Methods for working with degrees, arcminutes, and arcseconds.

    /**
     * Convert DMS (degrees, arcminutes, arcseconds) to decimal degrees.
     *
     * IMPORTANT: All parts SHOULD be either non-negative (i.e. 0 or positive) or non-positive
     * (i.e. 0 or negative). So, for example, if you want to convert -12°34′56″ to degrees, call
     * dmsToDeg(-12, -34, -56)
     * If you want to convert -12°56″ to degrees, call dmsToDeg(-12, 0, -56).
     *
     * @param float $deg The degrees part (should be an integer, but doesn't have to be).
     * @param float $arcmin The arcminutes part (should be an integer, but doesn't have to be).
     * @param float $arcsec The arcseconds part.
     * @return float The total degrees in the angle.
     */
    public static function dmsToDeg(float $deg, float $arcmin, float $arcsec): float
    {
        // Compute the total degrees.
        return $deg + $arcmin / self::ARCMINUTES_PER_DEGREE + $arcsec / self::ARCSECONDS_PER_DEGREE;
    }

    /**
     * Convert decimal degrees to DMS parts.
     *
     * @param float $deg The total degrees.
     * @param ?int $decimals Optional number of decimal places for the arcseconds.
     * @return array{0:int,1:int,2:float} [degrees, arcminutes, arcseconds]
     */
    public static function degToDms(float $deg, ?int $decimals = null): array
    {
        $sign    = $deg < 0 ? -1 : 1;
        $abs_deg = abs($deg);

        // Convert total degrees to parts.
        $d       = (int) floor($abs_deg);
        $m_float = ($abs_deg - $d) * self::ARCMINUTES_PER_DEGREE;
        $m       = (int) floor($m_float);
        $s       = ($m_float - $m) * self::ARCSECONDS_PER_ARCMINUTE;

        // Round seconds to requested precision (if any).
        if ($decimals !== null) {
            $s = round($s, $decimals);
        }

        // ---- Normalise carries ----
        // If seconds hit 60, carry to minutes.
        if ($s >= self::ARCSECONDS_PER_ARCMINUTE) {
            $s = 0.0;
            $m += 1;
        }

        // If minutes hit 60, carry to degrees.
        if ($m >= self::ARCMINUTES_PER_DEGREE) {
            $m = 0;
            $d += 1;
        }

        return [$sign * $d, $sign * $m, $sign * $s];
    }

    /**
     * Format angle given in degrees, arcminutes, and arcseconds as "D°M′S″".
     *
     * @param float $deg The degrees part (should be an integer, but doesn't have to be).
     * @param float $arcmin The arcminutes part (should be an integer, but doesn't have to be).
     * @param float $arcsec The arcseconds part.
     * @param ?int $decimals Optional number of decimal places for the arcseconds.
     * @return string The degrees, arcminutes, and arcseconds nicely formatted as a string.
     */
    public static function dmsToString(
        float $deg,
        float $arcmin,
        float $arcsec,
        ?int $decimals = null
    ): string {
        // Convert to total number of degrees.
        $deg_total = self::dmsToDeg($deg, $arcmin, $arcsec);

        // Return the formatted string.
        return self::degToDmsString($deg_total, $decimals);
    }

    /**
     * Format angle given in degrees as "D°M′S″".
     *
     * @param float $deg The total degrees.
     * @param ?int $decimals Optional number of decimal places for the arcseconds.
     * @return string The degrees, arcminutes, and arcseconds nicely formatted as a string.
     */
    public static function degToDmsString(float $deg, ?int $decimals = null): string
    {
        // Get the sign if negative.
        $sign = $deg < 0 ? '-' : '';

        // Convert the absolute value of the angle to DMS.
        // Be sure to pass decimals so rounding happens before carry.
        [$d, $m, $s] = self::degToDms(abs($deg), $decimals);

        // If the number of decimals is specified, format the arcseconds.
        $s_str = $decimals !== null ? sprintf("%.{$decimals}f", $s) : (string) $s;

        return "{$sign}{$d}°{$m}′{$s_str}″";
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Method for converting a string to an angle in degrees.

    /**
     * Checks that the input string, which is meant to indicate an angle, is valid.
     *
     * Different units (deg, rad, grad, turn) are supported, as per CSS.
     * There cannot be any spaces between the number and the unit.
     * @see https://developer.mozilla.org/en-US/docs/Web/CSS/angle
     *
     * The format with degrees, minutes, and seconds, as produced by the degToDmsString() method, is
     * also supported.
     * There cannot be any space between the number and the unit, but it's ok to have a single space
     * between the parts.
     *
     * If valid, the angle is returned in degrees normalized to the range [0-360).
     * Otherwise, an exception is thrown.
     *
     * @param string $value The string to parse.
     * @return float If the argument is valid, the angle in degrees.
     * @throws InvalidArgumentException If the argument is invalid.
     */
    public static function parse(string $value): float
    {
        // Convert angle string to degrees.
        $value = trim($value);

        // Prepare an error message with the original value.
        $err_msg = "The value '$value' does not represent a valid angle string.";

        // Check for the DMS pattern as returned by degToDmsString().
        // That means optional minus, then optional degrees, minutes, seconds.
        // At least one component is required.
        $num = '(\d+(?:\.\d+)?)';
        if (preg_match("/^(-?)(?:{$num}° ?)?(?:{$num}′ ?)?(?:{$num}″)?$/", $value, $matches)) {
            // At least one component must be present
            if (empty($matches[2]) && empty($matches[3]) && empty($matches[4])) {
                throw new InvalidArgumentException($err_msg);
            }

            // Extract the parts.
            $is_neg = $matches[1] === '-';
            $d = isset($matches[2]) ? (float)$matches[2] : 0.0;
            $m = isset($matches[3]) ? (float)$matches[3] : 0.0;
            $s = isset($matches[4]) ? (float)$matches[4] : 0.0;

            // Convert to decimal degrees.
            $deg_total = self::dmsToDeg($d, $m, $s);
            if ($is_neg) {
                $deg_total = -$deg_total;
            }
        } else {
            // Test for different units, as supported by CSS.
            if (str_ends_with($value, 'deg')) {
                $unit_length = 3;
                $conversion_factor = 1.0;
            } elseif (str_ends_with($value, 'grad')) {
                // Have to make sure we check for 'grad' before 'rad'.
                $unit_length = 4;
                $conversion_factor =  self::DEGREES_PER_GRADIAN;
            } elseif (str_ends_with($value, 'rad')) {
                $unit_length = 3;
                $conversion_factor = self::DEGREES_PER_RADIAN;
            } elseif (str_ends_with($value, 'turn')) {
                $unit_length = 4;
                $conversion_factor = self::DEGREES_PER_CIRCLE;
            } else {
                // No valid units.
                throw new InvalidArgumentException($err_msg);
            }

            // Remove the units.
            $value = substr($value, 0, -$unit_length);

            // Check we have a number.
            if (!is_numeric($value)) {
                throw new InvalidArgumentException($err_msg);
            }

            // Convert to degrees.
            $deg_total = (float)$value * $conversion_factor;
        }

        // Normalize to standard range [0-360).
        return self::wrapDegrees($deg_total);
    }
}
