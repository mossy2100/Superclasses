<?php

declare(strict_types=1);

namespace Superclasses;

use InvalidArgumentException;
use Stringable;

/**
 * Color class.
 *
 * @author Shaun Moss
 * @version 2012-08-11
 *
 * @idea Also provide properties for cyan, magenta, yellow, and black.
 *
 * IMPORTANT NOTE RE COLOR COMPONENTS
 * Color components (red, green, blue, alpha) can be provided as ints or floats. These have
 * different meanings.
 * - If an int, this is taken to be the byte value, and it must be in the range 0-255.
 * - If a float, this is taken to be the fraction, and it must be in the range 0.0-1.0.
 * There is a risk of confusion if a value equal to 1 is provided:
 *      - if it's an int the component's byte value will be 1
 *      - if it's a float (i.e. 1.0) then the component's byte value will be 255 (0xff)
 * So, be careful you don't pass integer 1 when you really mean 1.0 or '100%'.
 */
class Color implements Stringable
{
    private const MAX_U32 = 0xffffffff;

    /**
     * The color value, a 32-bit integer with bytes organised as RGBA (i.e. 0xRRGGBBAA).
     *
     * @var int
     */
    public int $value = 0 {
        set(int $value) {
            // Check the provided color value is in the valid range for 32-bit color.
            if ($value < 0 || $value > self::MAX_U32) {
                throw new InvalidArgumentException('Invalid color value. Must be in the range 0 to ' . self::MAX_U32 . ' (0xffffffff).');
            }

            $this->value = $value;
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Virtual properties

    public int $red {
        get => ($this->value >> 24) & 0xff;

        set(int|float|string $r) {
            // Check the provided value is valid.
            $r = self::checkColorComponent($r)[0];

            // Update the value.
            $this->value = ($this->value & 0x00ffffff) | ($r << 24);
        }
    }

    public int $green {
        get => ($this->value >> 16) & 0xff;

        set(int|float|string $g) {
            // Check the provided value is valid.
            $g = self::checkColorComponent($g)[0];

            // Update the value.
            $this->value = ($this->value & 0xff00ffff) | ($g << 16);
        }
    }

    public int $blue {
        get => ($this->value >> 8) & 0xff;

        set(int|float|string $b) {
            // Check the provided value is valid.
            $b = self::checkColorComponent($b)[0];

            // Update the value.
            $this->value = ($this->value & 0xffff00ff) | ($b << 8);
        }
    }

    public int $alpha {
        get => $this->value & 0xff;

        set(int|float|string $a) {
            // Check the provided value is valid.
            $a = self::checkColorComponent($a)[0];

            // Update the value.
            $this->value = ($this->value & 0xffffff00) | $a;
        }
    }

    public float $hue {
        get => $this->toHsl()['hue'];

        set(float|string $hue) {
            // Check the provided value is valid.
            $h = self::checkAngle($hue);

            // Get the current saturation and lightness values.
            ['saturation' => $s, 'lightness' => $l] = $this->toHsl();

            // Update the value.
            $this->_setRgbaBytesFromHsl($h, $s, $l);
        }
    }

    public float $saturation {
        get => $this->toHsl()['saturation'];

        set(float|string $saturation) {
            // Check the provided value is valid.
            $s = self::checkFrac($saturation);

            // Get the current hue and lightness values.
            ['hue' => $h, 'lightness' => $l] = $this->toHsl();

            // Update the value.
            $this->_setRgbaBytesFromHsl($h, $s, $l);
        }
    }

    public float $lightness {
        get => $this->toHsl()['lightness'];

        set(float|string $lightness) {
            // Check the provided value is valid.
            $l = self::checkFrac($lightness);

            // Get the current hue and saturation values.
            ['hue' => $h, 'saturation' => $s] = $this->toHsl();

            // Update the value.
            $this->_setRgbaBytesFromHsl($h, $s, $l);
        }
    }

    /**
     * Constructor.
     *
     * Accepts a CSS color name or a hex string (3, 4, 6, or 8 digits, with or
     * without a leading '#').
     *
     * @param int|string $color The 32-bit color value or color string.
     * @throws InvalidArgumentException If the provided color value is invalid.
     */
    public function __construct(int|string $color = 0)
    {
        // If we have an int, set the property. That will check the range.
        if (is_int($color)) {
            $this->value = $color;
            return;
        }

        // Argument is a string, convert to RGBA bytes.
        $rgba = self::colorStringToRgba($color);
        $this->_setRgbaBytes($rgba['red'], $rgba['green'], $rgba['blue'], $rgba['alpha']);
    }

    /**
     * SetOf the red, green, blue, and alpha components all at the same time.
     * This is an internal function and arguments are assumed to be valid.
     *
     * @param int $red The red component as a byte.
     * @param int $green The green component as a byte.
     * @param int $blue The blue component as a byte.
     * @param int $alpha The alpha value as a byte.
     */
    protected function _setRgbaBytes(int $red, int $green, int $blue, int $alpha): void
    {
        $this->value = ($red << 24) | ($green << 16) | ($blue << 8) | $alpha;
    }

    /**
     * Sets the RGB components of this color from HSL values, preserving the current alpha.
     * This is an internal function and arguments are assumed to be valid.
     *
     * @param float $h The hue in degrees (0.0–360.0).
     * @param float $s The saturation as a fraction (0.0–1.0).
     * @param float $l The lightness as a fraction (0.0–1.0).
     */
    protected function _setRgbaBytesFromHsl(float $h, float $s, float $l): void
    {
        ['red' => $r, 'green' => $g, 'blue' => $b] = self::hslToRgb($h, $s, $l);
        $this->_setRgbaBytes($r, $g, $b, $this->alpha);
    }

    /**
     * SetOf the red, green, blue, and alpha components all at the same time.
     *
     * @param int|float|string $red The red component as a byte, fraction, or percentage string.
     * @param int|float|string $green The green component as a byte, fraction, or percentage string.
     * @param int|float|string $blue The blue component as a byte, fraction, or percentage string.
     * @param int|float|string $alpha The alpha value as a byte, fraction, or percentage string (optional, defaults to 0xff).
     * @throws InvalidArgumentException If any inputs are invalid.
     */
    public function setRgba(
        int|float|string $red,
        int|float|string $green,
        int|float|string $blue,
        int|float|string $alpha = 0xff
    ) {
        // Check the arguments and get the byte values.
        $r = self::checkColorComponent($red)[0];
        $g = self::checkColorComponent($green)[0];
        $b = self::checkColorComponent($blue)[0];
        $a = self::checkColorComponent($alpha)[0];

        // SetOf the byte values.
        $this->_setRgbaBytes($r, $g, $b, $a);
    }

    /**
     * Parse a color string into RGBA component bytes.
     *
     * @param string $color
     * @return array The RGBA component bytes of the color.
     * @throws InvalidArgumentException If the string is not a valid color (named or hex).
     */
    public static function colorStringToRgba(string $color): array
    {
        $str = trim($color);

        // Transparent.
        if (strcasecmp($str, 'transparent') === 0) {
            return ['red' => 0, 'green' => 0, 'blue' => 0, 'alpha' => 0];
        }

        // Named color.
        if (self::isColorName($str)) {
            $str = self::colorNameToHex($str);
        }

        // Convert hex color string to RGBA color component bytes.
        $rgba = self::hexToRgba($str);

        // Check if invalid.
        if ($rgba === null) {
            throw new InvalidArgumentException("Invalid color string: '$color'.");
        }

        return $rgba;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Factory methods

    /**
     * Create a color from RGBA values.
     *
     * @param int|float|string $red The red component as a byte, fraction, or percentage string.
     * @param int|float|string $green The green component as a byte, fraction, or percentage string.
     * @param int|float|string $blue The blue component as a byte, fraction, or percentage string.
     * @param int|float|string $alpha The alpha value as a byte, fraction, or percentage string (optional, defaults to 100%).
     * @return self
     * @throws InvalidArgumentException If any inputs are invalid.
     */
    public static function fromRgba(
        int|float|string $red,
        int|float|string $green,
        int|float|string $blue,
        int|float|string $alpha = 0xff
    ): self {
        $color = new self();
        $color->setRgba($red, $green, $blue, $alpha);
        return $color;
    }

    /**
     * Create a color from HSLA values.
     *
     * @param float|string $hue The hue in degrees or as an angle string.
     * @param float|string $saturation The saturation as a fraction or percentage string.
     * @param float|string $lightness The lightness as a fraction or percentage string.
     * @param float|string $alpha The alpha value as a byte, fraction, or percentage string (optional, defaults to 100%).
     *
     * @return self
     */
    public static function fromHsla(
        float|string $hue,
        float|string $saturation,
        float|string $lightness,
        float|string $alpha = 0xff
    ): self {
        // Check the arguments.
        $h = self::checkAngle($hue);
        $s = self::checkFrac($saturation);
        $l = self::checkFrac($lightness);
        $a = self::checkColorComponent($alpha)[0];

        // Convert the HSL components to RGB components.
        ['red' => $r, 'green' => $g, 'blue' => $b] = self::hslToRgb($h, $s, $l);

        // Construct a new Color.
        return self::fromRgba($r, $g, $b, $a);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Methods for converting a Color to an array.

    /**
     * Gets RGB color components as an array.
     *
     * @return array Array of color components as bytes.
     */
    public function toRgb(): array
    {
        return [
            'red'   => $this->red,
            'green' => $this->green,
            'blue'  => $this->blue
        ];
    }

    /**
     * Gets all RGBA values as an array.
     *
     * @return array Array of color components as bytes.
     */
    public function toRgba(): array
    {
        return [
            'red'   => $this->red,
            'green' => $this->green,
            'blue'  => $this->blue,
            'alpha' => $this->alpha
        ];
    }

    /**
     * Returns the Color as an array of numbers representing HSL values.
     *
     * Hue is represented as an angle in degrees (0-360).
     * Saturation and lightness are represented as fractions (0.0-1.0).
     *
     * @return array Array of color components as numbers.
     */
    public function toHsl(): array
    {
        return self::rgbToHsl($this->red, $this->green, $this->blue);
    }

    /**
     * Returns the Color as an array of numbers representing HSLA values.
     *
     * Hue is represented as an angle in degrees (0-360).
     * Saturation and lightness are represented as fractions (0.0-1.0).
     * Alpha is represented as a byte (0-255).
     *
     * @return array Array of color components as numbers.
     */
    public function toHsla(): array
    {
        $hsla = self::rgbToHsl($this->red, $this->green, $this->blue);
        $hsla['alpha'] = $this->alpha;
        return $hsla;
    }

    /**
     * Get the color as an informative array, with:
     *   red
     *   green
     *   blue
     *   alpha
     *   hue
     *   saturation
     *   lightness
     *   hex
     *
     * @return array An array of color properties.
     */
    public function toArray(): array
    {
        return array_merge(
            $this->toRgba(),
            $this->toHsl(),
            ['hex' => $this->toHexString()]
        );
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Lightness-related methods

    /**
     * Returns true for a dark color.
     *
     * @return  bool
     */
    public function isDark()
    {
        return $this->lightness < 0.5;
    }

    /**
     * Returns true for a light color.
     *
     * @return  bool
     */
    public function isLight()
    {
        return $this->lightness >= 0.5;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Color mixing methods

    /**
     * Mix two colors.
     * If called with only two parameters then the colors are mixed 50-50.
     *
     * @param self $color2
     * @param float|string $frac The fraction of the "this" color expressed as a float
     *     (0.0 - 1.0) or percentage string (e.g. '50%').
     * @return self
     */
    public function mix(Color $color2, float|string $frac = 0.5): self
    {
        // Validate fraction.
        $frac = self::checkFrac($frac);

        // Check for 100% this color.
        $eps  = 1e-9;
        if ($frac >= 1.0 - $eps) {
            return clone $this;
        }
        // Check for 0% this color.
        if ($frac <= $eps) {
            return clone $color2;
        }

        // Compute the components of the new color.
        $frac2 = 1.0 - $frac;
        $r = (int)round(($this->red   * $frac) + ($color2->red   * $frac2));
        $g = (int)round(($this->green * $frac) + ($color2->green * $frac2));
        $b = (int)round(($this->blue  * $frac) + ($color2->blue  * $frac2));
        $a = (int)round(($this->alpha * $frac) + ($color2->alpha * $frac2));

        // Create and return the mixed color.
        return self::fromRgba($r, $g, $b, $a);
    }

    /**
     * Blend two colors.
     *
     * The $this color is the top color, and the argumentnt is the bottom color.
     *
     * This method is for setting one pixel ($color1) on top of another ($color2) on an image.
     *
     * For formulas:
     * @see http://www.w3.org/TR/2003/REC-SVG11-20030114/masking.html#SimpleAlphaBlending
     * @see http://en.wikipedia.org/wiki/Alpha_compositing#Alpha_blending
     *
     * @param self $bottom_color The underneath color.
     * @return self The resulting color.
     */
    public function blend(Color $bottom_color)
    {
        // Calculate resultant alpha.
        $a1 = self::byteToFrac($this->alpha);
        $a2 = self::byteToFrac($bottom_color->alpha);
        $a3 = $a1 + $a2 * (1 - $a1);

        // Calculate red, green and blue components of resultant color:
        $rgb1 = $this->toRgba();
        $rgb2 = $bottom_color->toRgba();
        $rgb3 = [];
        foreach (['red', 'green', 'blue'] as $channel) {
            $c1 = $rgb1[$channel] / 255;
            $c2 = $rgb2[$channel] / 255;
            $c3 = (($c1 * $a1) + ($c2 * $a2 * (1 - $a1))) / $a3;
            $rgb3[$channel] = self::fracToByte($c3);
        }

        // Create and return new color.
        return self::fromRgba($rgb3['red'], $rgb3['green'], $rgb3['blue'], self::fracToByte($a3));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Methods to validate arguments

    /**
     * Checks that the input value, which is meant to indicate a color component's value, is valid.
     * The value can be:
     *  - an integer in the range 0 - 255
     *  - a float in the range 0.0 - 1.0
     *  - a percentage string (e.g. '50%')
     *
     * @param int|float|string $value The value to check.
     * @return array If the argument is valid, the equivalent byte and fraction.
     * @throws InvalidArgumentException If the argument is invalid.
     */
    private static function checkColorComponent(int|float|string $value): array
    {
        // SetOf the error message.
        $err_msg = "The value '$value' is invalid. A color component (red, green, blue, or alpha) must be provided as an integer in the range 0 to 255, a float in the range 0.0 to 1.0, or a percentage string (e.g. '50%').";

        // Check int.
        if (is_int($value)) {
            // Check the range.
            if ($value < 0 || $value > 255) {
                throw new InvalidArgumentException($err_msg);
            }

            // Looks good.
            return [$value, $value / 255.0];
        }

        // Check float.
        if (is_float($value)) {
            // Check the range.
            if ($value < 0 || $value > 1) {
                throw new InvalidArgumentException($err_msg);
            }

            // Convert to byte.
            return [self::fracToByte($value), $value];
        }

        // Must be a string. Get the percentage as a fraction.
        $frac = self::percentToFrac($value);
        if ($frac === null) {
            throw new InvalidArgumentException($err_msg);
        }

        // Convert to a byte.
        return [self::fracToByte($frac), $frac];
    }

    /**
     * Checks that the input value, which is meant to indicate an angle, is valid.
     *
     * If the argument is a number then it is treated as degrees. Negative values are ok.
     *
     * If the argument is a string, different units (deg, rad, grad, turn) are supported,
     * as per CSS, plus also the degree symbol.
     * There cannot be any spaces between the number and the unit.
     * @see https://developer.mozilla.org/en-US/docs/Web/CSS/angle
     *
     * If valid, the angle is returned in degrees normalized to the range [0-360).
     * Otherwise, an exception is thrown.
     *
     * The purpose of the method is to check a value provided for hue.
     *
     * @param float|string $value The value to check.
     * @return float If the argument is valid, the angle in degrees.
     * @throws InvalidArgumentException If the argument is invalid.
     */
    private static function checkAngle(float|string $value): float
    {
        // Convert angle string to degrees.
        if (is_string($value)) {
            return Angle::fromString($value)->toDegrees();
        }

        // Normalize number to desired range [0-360).
        return Angle::wrapDegrees($value);
    }

    /**
     * Check that a value provided for a fraction is valid.
     * If a float, it must be in the range 0 - 1.
     * If a string, it must be a valid percentage string (e.g. '50%').
     * If valid, return the value as a fraction (float). Otherwise, throw an exception.
     */
    private static function checkFrac(float|string $value): float
    {
        $s = is_string($value) ? self::percentToFrac($value) : self::numToFrac($value);

        if ($s === null) {
            throw new InvalidArgumentException("Value '$value' is invalid. Fractional values must be provided either as a float in the range 0.0 to 1.0, or as a percentage string (e.g. '50%').");
        }

        return $s;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Methods to convert values from one form to another

    /**
     * Convert an input number to a fraction in the range 0.0 to 1.0.
     */
    public static function numToFrac(float $num): ?float
    {
        // Check the number is in range.
        if ($num < 0 || $num > 1) {
            return null;
        }

        // Convert to a float.
        return (float)$num;
    }

    /**
     * Converts the provided percentage string into a fraction in the range 0.0 to 1.0.
     *
     * A valid percentage string means a number in the range 0.0 to 100.0, followed by a '%'
     * character.
     * No leading sign character (- or +) or floating point notation (e or E) is allowed.
     *
     * @param string $str A string that could be a percentage string.
     * @return ?float The equivalent fraction, or null if the string was invalid.
     */
    public static function percentToFrac(string $str): ?float
    {
        // Check the string has the right format.
        if (!preg_match('/^\d+(\.\d+)?%$/', $str)) {
            return null;
        }

        // Convert to float and check the range.
        $value = (float)substr($str, 0, -1);
        if ($value > 100.0) {
            return null;
        }

        // Convert to a fraction.
        return $value / 100.0;
    }

    /**
     * Convert a fraction in the range 0.0 to 1.0 into a percentage string (e.g. '50%').
     */
    public static function fracToPercent(float $frac): ?string
    {
        // Check the range is valid.
        if ($frac < 0 || $frac > 1) {
            return null;
        }

        return round($frac * 100, 2) . '%';
    }

    /**
     * Convert a byte to a fraction. If the value is out of range, return null.
     *
     * @param int $byte The byte value.
     * @return float The fraction, or null.
     */
    public static function byteToFrac(int $byte): ?float
    {
        // Check the range.
        if ($byte < 0 || $byte > 255) {
            return null;
        }

        return round($byte / 255.0, 2);
    }

    /**
     * Convert a fraction to a byte. If the value is out of range, return null.
     *
     * @param float $frac The fraction to convert.
     * @param int The byte value, or null.
     */
    public static function fracToByte(float $frac): ?int
    {
        // Check the range.
        if ($frac < 0 || $frac > 1) {
            return null;
        }

        return (int)round($frac * 255.0);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Methods for working with hexadecimal strings

    /**
     * Returns true if the string is a valid hex color string.
     * A leading '#' is optional, and there can be 3, 4, 6, or 8 hex digits.
     *
     * @param string $str A string that could be a CSS hex color.
     * @return bool If the provided string is a valid CSS hex color string.
     */
    public static function isHexString(string $str): bool
    {
        // Check for invalid length.
        $len = strlen($str);
        if ($len === 0 || $len > 9) {
            return false;
        }

        // Trim the leading '#' if present.
        if ($str[0] === '#') {
            $str = substr($str, 1);
            $len--;
        }

        // Check the string is all hex digits and length is 3, 4, 6 or 8.
        return ctype_xdigit($str) && in_array($len, [3, 4, 6, 8], true);
    }

    /**
     * Convert a hexadecimal color string to RGBA component bytes.
     * A leading '#' is optional.
     * Each element of the resulting array is a byte (integer in the range 0 to 255), including
     * alpha.
     *
     * @param string A hexadecimal color string.
     * @return ?array An array of color component bytes, or null if the input was invalid.
     */
    public static function hexToRgba(string $hex): ?array
    {
        // Check the input string is a valid format.
        if (!self::isHexString($hex)) {
            return null;
        }

        // Remove any leading # character:
        if ($hex[0] == '#') {
            $hex = substr($hex, 1);
        }

        $len = strlen($hex);
        if ($len === 8) {
            // 8-digit hex string (RRGGBBAA).
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $a = hexdec(substr($hex, 6, 2));
        } elseif ($len === 6) {
            // 6-digit hex string (RRGGBB).
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $a = 0xff;
        } elseif ($len === 4) {
            // 4-digit hex string (RGBA). Double each hex digit.
            $r = hexdec(str_repeat($hex[0], 2));
            $g = hexdec(str_repeat($hex[1], 2));
            $b = hexdec(str_repeat($hex[2], 2));
            $a = hexdec(str_repeat($hex[3], 2));
        } else {
            // 3-digit hex string (RGB). Double each hex digit:
            $r = hexdec(str_repeat($hex[0], 2));
            $g = hexdec(str_repeat($hex[1], 2));
            $b = hexdec(str_repeat($hex[2], 2));
            $a = 0xff;
        }

        $result = [
          'red' => $r,
          'green' => $g,
          'blue' => $b,
          'alpha' => $a
        ];

        return $result;
    }

    /**
     * Outputs the color as a 6-digit hexadecimal string, or 8-digit if alpha is included.
     *
     * @param bool $include_alpha If the alpha byte should be included (i.e. RGBA or RGB).
     * @param bool $include_hash If the resulting string should have a leading '#'.
     * @param bool $upper_case If the letter digits should be upper-case.
     * @return string The color formated as a hexadecimal string.
     */
    public function toHexString(
        bool $include_alpha = true,
        bool $include_hash = true,
        bool $upper_case = false
    ): string {
        // Ensure we only have the lower 32 bits.
        $value = $this->value & 0xFFFFFFFF;

        // Convert to hex and pad to 8 characters (full RGBA).
        $hex = str_pad(dechex($value), 8, '0', STR_PAD_LEFT);

        // Optionally drop alpha (last 2 hex digits).
        if (!$include_alpha) {
            $hex = substr($hex, 0, 6);
        }

        // Apply letter case.
        $hex = $upper_case ? strtoupper($hex) : strtolower($hex);

        // Add optional hash.
        return ($include_hash ? '#' : '') . $hex;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Methods for converting a Color to a string

    /**
     * Outputs the color as an RGB CSS string.
     *
     * @return string
     */
    public function toRgbString()
    {
        return "rgb($this->red, $this->green, $this->blue)";
    }

    /**
     * Outputs the color as an RGBA CSS string.
     *
     * @return string
     */
    public function toRgbaString()
    {
        $a = self::byteToFrac($this->alpha);
        return "rgba($this->red, $this->green, $this->blue, $a)";
    }

    /**
     * Outputs the color as an HSL CSS string.
     *
     * @return string
     */
    public function toHslString()
    {
        $hsla = $this->toHsl();
        $h = round($hsla['hue'], 2);
        $s = self::fracToPercent($hsla['saturation']);
        $l = self::fracToPercent($hsla['lightness']);
        return "hsl($h, $s, $l)";
    }

    /**
     * Outputs the color as an HSLA CSS string.
     *
     * @return string
     */
    public function toHslaString()
    {
        $hsla = $this->toHsla();
        $h = round($hsla['hue'], 2);
        $s = self::fracToPercent($hsla['saturation']);
        $l = self::fracToPercent($hsla['lightness']);
        $a = self::byteToFrac($this->alpha);
        return "hsla($h, $s, $l, $a)";
    }

    /**
     * Stringable implementation.
     *
     * Default string representation of color is 8-digit RGBA hex string with leading '#'.
     * Suitable for use in CSS.
     *
     * @return string The Color as a CSS hexadecimal color string (RGBA, 8 digits).
     */
    public function __toString(): string
    {
        return $this->toHexString();
    }

    /////////////////////////////////////////////////////////////////////////
    // Static methods for converting between RGB, HSL and hex.

    /**
     * Convert RGB values to HSL.
     *
     * Algorithms:
     *   @see http://www.w3.org/TR/css3-color/#hsl-color
     *   @see http://130.113.54.154/~monger/hsl-rgb.html
     *   @see http://en.wikipedia.org/wiki/HSL_color_space
     *
     * @param int|float|string $red The red byte value.
     * @param int|float|string $green The green byte value.
     * @param int|float|string $blue The blue byte value.
     * @return array Array of floats with HSL values:
     *   hue        => 0..360
     *   saturation => 0.0..1.0
     *   lightness  => 0.0..1.0
     */
    public static function rgbToHsl(
        int|float|string $red,
        int|float|string $green,
        int|float|string $blue
    ) {
        // Get the red, green and blue values as fractions:
        $r = self::checkColorComponent($red)[1];
        $g = self::checkColorComponent($green)[1];
        $b = self::checkColorComponent($blue)[1];

        // Get the min and max values.
        $min = min($r, $g, $b);
        $max = max($r, $g, $b);

        // Calculate lightness:
        $l = ($min + $max) / 2;
        if ($max === $min) {
            $s = 0;
            $h = 0;
        } else {
            $d = $max - $min;
            $s = ($l < 0.5)
                ? $d / ($max + $min)
                : $d / (2 - $max - $min);

            if ($r >= $g && $r >= $b) { // red is max
                $h = ($g - $b) / $d;
            } elseif ($g >= $r && $g >= $b) { // green is max
                $h = 2 + ($b - $r) / $d;
            } else { // blue is max
                $h = 4 + ($r - $g) / $d;
            }

            $h = Angle::wrapDegrees($h * 60);
        }

        return [
          'hue'        => $h,
          'saturation' => $s,
          'lightness'  => $l
        ];
    }

    /**
     * Convert HSL values to RGB.
     * @see https://en.wikipedia.org/wiki/HSL_and_HSV#HSL_to_RGB_alternative
     *
     * @param float|string $hue The hue as an angle in degrees or an angle string.
     * @param float|string $saturation The saturation as a fraction or a percentage string.
     * @param float|string $lightness The lightness as a fraction or a percentage string.
     * @return array An array of red, green, and blue bytes.
     *
     */
    public static function hslToRgb(
        float|string $hue,
        float|string $saturation,
        float|string $lightness
    ): array {
        // Convert values to floats in the desired ranges.
        $h = self::checkAngle($hue); // [0-360)
        $s = self::checkFrac($saturation); // [0-1]
        $l = self::checkFrac($lightness); // [0-1]

        // Conversion function.
        $f = function ($n) use ($h, $s, $l): float {
            $k = fmod($n + $h / 30, 12);
            $a = $s * min($l, 1 - $l);
            return $l - $a * max(-1, min($k - 3, 9 - $k, 1));
        };

        return [
          'red'   => $f(0),
          'green' => $f(8),
          'blue'  => $f(4)
        ];
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Named colors

    /**
     * Array for mapping color names to hex values.
     *
     * @var array
     */
    protected static $colorNames = [
        'aliceblue'            => 'f0f8ff',
        'amethyst'             => '9966cc',
        'antiquewhite'         => 'faebd7',
        'aqua'                 => '00ffff',
        'aquamarine'           => '7fffd4',
        'azure'                => 'f0ffff',
        'beige'                => 'f5f5dc',
        'bisque'               => 'ffe4c4',
        'black'                => '000000',
        'blanchedalmond'       => 'ffebcd',
        'blue'                 => '0000ff',
        'blueviolet'           => '8a2be2',
        'brown'                => 'a52a2a',
        'burlywood'            => 'deb887',
        'cadetblue'            => '5f9ea0',
        'chartreuse'           => '7fff00',
        'chocolate'            => 'd2691e',
        'coral'                => 'ff7f50',
        'cornflowerblue'       => '6495ed',
        'cornsilk'             => 'fff8dc',
        'crimson'              => 'dc143c',
        'cyan'                 => '00ffff',
        'darkblue'             => '00008b',
        'darkcyan'             => '008b8b',
        'darkgoldenrod'        => 'b8860b',
        'darkgray'             => 'a9a9a9',
        'darkgreen'            => '006400',
        'darkgrey'             => 'a9a9a9',
        'darkkhaki'            => 'bdb76b',
        'darkmagenta'          => '8b008b',
        'darkolivegreen'       => '556b2f',
        'darkorange'           => 'ff8c00',
        'darkorchid'           => '9932cc',
        'darkred'              => '8b0000',
        'darksalmon'           => 'e9967a',
        'darkseagreen'         => '8fbc8f',
        'darkslateblue'        => '483d8b',
        'darkslategray'        => '2f4f4f',
        'darkslategrey'        => '2f4f4f',
        'darkturquoise'        => '00ced1',
        'darkviolet'           => '9400d3',
        'deeppink'             => 'ff1493',
        'deepskyblue'          => '00bfff',
        'dimgray'              => '696969',
        'dimgrey'              => '696969',
        'dodgerblue'           => '1e90ff',
        'firebrick'            => 'b22222',
        'floralwhite'          => 'fffaf0',
        'forestgreen'          => '228b22',
        'fuchsia'              => 'ff00ff',
        'gainsboro'            => 'dcdcdc',
        'ghostwhite'           => 'f8f8ff',
        'gold'                 => 'ffd700',
        'goldenrod'            => 'daa520',
        'gray'                 => '808080',
        'green'                => '008000',
        'greenyellow'          => 'adff2f',
        'grey'                 => '808080',
        'honeydew'             => 'f0fff0',
        'hotpink'              => 'ff69b4',
        'indianred'            => 'cd5c5c',
        'indigo'               => '4b0082',
        'ivory'                => 'fffff0',
        'khaki'                => 'f0e68c',
        'lavender'             => 'e6e6fa',
        'lavenderblush'        => 'fff0f5',
        'lawngreen'            => '7cfc00',
        'lemonchiffon'         => 'fffacd',
        'lightblue'            => 'add8e6',
        'lightcoral'           => 'f08080',
        'lightcyan'            => 'e0ffff',
        'lightgoldenrodyellow' => 'fafad2',
        'lightgray'            => 'd3d3d3',
        'lightgreen'           => '90ee90',
        'lightgrey'            => 'd3d3d3',
        'lightpink'            => 'ffb6c1',
        'lightsalmon'          => 'ffa07a',
        'lightseagreen'        => '20b2aa',
        'lightskyblue'         => '87cefa',
        'lightslategray'       => '778899',
        'lightslategrey'       => '778899',
        'lightsteelblue'       => 'b0c4de',
        'lightyellow'          => 'ffffe0',
        'lime'                 => '00ff00',
        'limegreen'            => '32cd32',
        'linen'                => 'faf0e6',
        'magenta'              => 'ff00ff',
        'maroon'               => '800000',
        'mediumaquamarine'     => '66cdaa',
        'mediumblue'           => '0000cd',
        'mediumorchid'         => 'ba55d3',
        'mediumpurple'         => '9370db',
        'mediumseagreen'       => '3cb371',
        'mediumslateblue'      => '7b68ee',
        'mediumspringgreen'    => '00fa9a',
        'mediumturquoise'      => '48d1cc',
        'mediumvioletred'      => 'c71585',
        'midnightblue'         => '191970',
        'mintcream'            => 'f5fffa',
        'mistyrose'            => 'ffe4e1',
        'moccasin'             => 'ffe4b5',
        'navajowhite'          => 'ffdead',
        'navy'                 => '000080',
        'oldlace'              => 'fdf5e6',
        'olive'                => '808000',
        'olivedrab'            => '6b8e23',
        'orange'               => 'ffa500',
        'orangered'            => 'ff4500',
        'orchid'               => 'da70d6',
        'palegoldenrod'        => 'eee8aa',
        'palegreen'            => '98fb98',
        'paleturquoise'        => 'afeeee',
        'palevioletred'        => 'db7093',
        'papayawhip'           => 'ffefd5',
        'peachpuff'            => 'ffdab9',
        'peru'                 => 'cd853f',
        'pink'                 => 'ffc0cb',
        'plum'                 => 'dda0dd',
        'powderblue'           => 'b0e0e6',
        'purple'               => '800080',
        'red'                  => 'ff0000',
        'rosybrown'            => 'bc8f8f',
        'royalblue'            => '4169e1',
        'saddlebrown'          => '8b4513',
        'salmon'               => 'fa8072',
        'sandybrown'           => 'f4a460',
        'seagreen'             => '2e8b57',
        'seashell'             => 'fff5ee',
        'sienna'               => 'a0522d',
        'silver'               => 'c0c0c0',
        'skyblue'              => '87ceeb',
        'slateblue'            => '6a5acd',
        'slategray'            => '708090',
        'slategrey'            => '708090',
        'snow'                 => 'fffafa',
        'springgreen'          => '00ff7f',
        'steelblue'            => '4682b4',
        'tan'                  => 'd2b48c',
        'teal'                 => '008080',
        'thistle'              => 'd8bfd8',
        'tomato'               => 'ff6347',
        'turquoise'            => '40e0d0',
        'violet'               => 'ee82ee',
        'wheat'                => 'f5deb3',
        'white'                => 'ffffff',
        'whitesmoke'           => 'f5f5f5',
        'yellow'               => 'ffff00',
        'yellowgreen'          => '9acd32',
    ];

    /**
     * Return the array of color names.
     *
     * @static
     * @return array
     */
    public static function colorNames()
    {
        return self::$colorNames;
    }

    /**
     * Check if a given string is a valid CSS color name.
     *
     * @static
     * @param string $name A color name.
     * @return bool If the name is a valid CSS color name.
     */
    public static function isColorName($name)
    {
        return isset(self::$colorNames[strtolower($name)]);
    }

    /**
     * Convert a color name to a 6-digit hex value (no leading '#').
     *
     * @static
     * @param string $name A CSS color name.
     * @return string The hex value for this color.
     */
    private static function colorNameToHex(string $name): string
    {
        // Check the provided color name is valid.
        if (!self::isColorName($name)) {
            throw new InvalidArgumentException("Invalid color name '$name'.");
        }

        // Look up the hex value for the color.
        return self::$colorNames[strtolower($name)];
    }
}
