<?php

declare(strict_types = 1);

namespace Superclasses;

use ValueError;
use Stringable;

/**
 * Color class.
 *
 * @author Shaun Moss
 * @version 2025-08-14
 */
class Color implements Stringable
{
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Internal representation

    /**
     * Internal field to store color value.
     *
     * The 32-bit color value stored as a 4-byte binary string (RGBA order).
     *
     * This approach was adopted over 32-bit unsigned int, which would not have worked on 32-bit systems because of
     * the way PHP converts ints to floats when they're outside the int range.
     * This approach is also preferable to storing 4 individual bytes for the color components, as that would
     * consume 16 bytes on a 32-bit system or 32 bytes on a 64-bit system.
     *
     * @var string
     */
    private string $_rgba;

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Constants

    /**
     * Constants for computing perceived lightness.
     *
     * @var float
     */
    private const float EPSILON = 216 / 24389; // 0.008856451679...
    private const float KAPPA = 24389 / 27;  // 903.296296...

    /**
     * Small value for float comparisons.
     *
     * @var float
     */
    public const float DELTA = 1e-9;

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Virtual properties

    /**
     * Get/set the red component of the color.
     *
     * @var int
     */
    public int $red {
        get => $this->_getByte(0);

        set {
            $this->_setByte(0, $value);
        }
    }

    /**
     * Get/set the green component of the color.
     *
     * @var int
     */
    public int $green {
        get => $this->_getByte(1);

        set {
            $this->_setByte(1, $value);
        }
    }

    /**
     * Get/set the blue component of the color.
     *
     * @var int
     */
    public int $blue {
        get => $this->_getByte(2);

        set {
            $this->_setByte(2, $value);
        }
    }

    /**
     * Get/set the alpha component of the color.
     *
     * @var int
     */
    public int $alpha {
        get => $this->_getByte(3);

        set {
            $this->_setByte(3, $value);
        }
    }

    /**
     * Get/set the hue of the color.
     *
     * @var float
     */
    public float $hue {
        get => $this->toHsla()['hue'];

        set {
            // Get the current saturation and lightness values.
            ['saturation' => $s, 'lightness' => $l] = $this->toHsla();

            // Update the value.
            $this->_setRgbaBytesFromHsl($value, $s, $l);
        }
    }

    /**
     * Get/set the saturation of the color.
     *
     * @var float
     */
    public float $saturation {
        get => $this->toHsla()['saturation'];

        set {
            // Check the provided value is in the range [0.0, 1.0].
            self::_validateFrac($value);

            // Get the current hue and lightness values.
            ['hue' => $h, 'lightness' => $l] = $this->toHsla();

            // Update the value.
            $this->_setRgbaBytesFromHsl($h, $value, $l);
        }
    }

    /**
     * Get/set the lightness of the color.
     *
     * @var float
     */
    public float $lightness {
        get => $this->toHsla()['lightness'];

        set {
            // Check the provided value is in the range [0.0, 1.0].
            self::_validateFrac($value);

            // Get the current hue and saturation values.
            ['hue' => $h, 'saturation' => $s] = $this->toHsla();

            // Update the value.
            $this->_setRgbaBytesFromHsl($h, $s, $value);
        }
    }

    /**
     * Get the relative luminance of the color in the range [0.0, 1.0].
     *
     * @see https://www.w3.org/WAI/GL/wiki/Relative_luminance
     * @see https://en.wikipedia.org/wiki/Relative_luminance
     *
     * @var float
     */
    public float $relativeLuminance {
        get {
            // Calculate linear RGB components using the gamma transfer function.
            $rlin = self::gamma($this->red);
            $glin = self::gamma($this->green);
            $blin = self::gamma($this->blue);

            // Compute the relative luminance.
            $y = 0.2126 * $rlin + 0.7152 * $glin + 0.0722 * $blin;

            // Clamp to range [0.0, 1.0] just to be sure.
            return self::_clamp($y);
        }
    }

    /**
     * Get the perceived lightness of the color in the range [0.0, 1.0].
     *
     * @var float
     */
    public float $perceivedLightness {
        get {
            // Start with the relative luminance.
            $y = $this->relativeLuminance;

            // Compute CIE L* (perceived lightness) as a value in the range [0, 100].
            $l_star = ($y <= self::EPSILON) ? (self::KAPPA * $y) : (116.0 * ($y ** (1.0 / 3.0)) - 16.0);

            // Normalize to [0.0, 1.0].
            return self::_clamp($l_star / 100.0);
        }
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Constructor and factory methods

    /**
     * Construct a new color from a string.
     *
     * Accepts a CSS color name or a hex string (3, 4, 6, or 8 hex digits, with or without a leading '#').
     * Defaults to black.
     *
     * @param string $color The color as a CSS hexadecimal color string or named color.
     * @throws ValueError if the provided string is not a valid CSS color.
     */
    public function __construct(string $color = 'black')
    {
        $c = self::colorStringToBytes($color);
        $this->_setRgbaBytes($c['red'], $c['green'], $c['blue'], $c['alpha']);
    }

    /**
     * Create a color from RGBA values.
     *
     * @static
     * @param int $red The red component byte value.
     * @param int $green The green component byte value.
     * @param int $blue The blue component byte value.
     * @param int $alpha Optional alpha value as a byte. Defaults to 0xff, which is equivalent to 100% opacity.
     * @return self
     * @throws ValueError If any inputs are invalid.
     */
    public static function fromRgba(int $red, int $green, int $blue, int $alpha = 0xff): self
    {
        $color = new self();

        // Check the arguments.
        self::_validateByte($red);
        self::_validateByte($green);
        self::_validateByte($blue);
        self::_validateByte($alpha);

        // Set the byte values.
        $color->_setRgbaBytes($red, $green, $blue, $alpha);

        return $color;
    }

    /**
     * Create a color from HSLA values.
     *
     * @static
     * @param float $hue The hue in degrees.
     * @param float $saturation The saturation as a fraction in the range [0.0, 1.0].
     * @param float $lightness The lightness as a fraction in the range [0.0, 1.0].
     * @param int $alpha Optional alpha value as a byte. Defaults to 0xff, which is equivalent to 100% opacity.
     * @return self
     */
    public static function fromHsla(float $hue, float $saturation, float $lightness, int $alpha = 0xff): self
    {
        // Check the arguments.
        $hue = Angle::wrapDegrees($hue);
        self::_validateFrac($saturation);
        self::_validateFrac($lightness);
        self::_validateByte($alpha);

        // Convert the HSL components to RGB components.
        ['red' => $red, 'green' => $green, 'blue' => $blue] = self::hslToRgb($hue, $saturation, $lightness);

        // Construct a new Color.
        return self::fromRgba($red, $green, $blue, $alpha);
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Methods for converting a Color to an array.

    /**
     * Gets all RGBA values as an array.
     *
     * @return array{red:int, green:int, blue:int, alpha:int} Array of color components as bytes.
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
     * Returns the Color as an array of numbers representing HSLA values.
     *
     * Hue is represented as an angle in degrees [0, 360).
     * Saturation and lightness are represented as fractions [0.0, 1.0].
     * Alpha is represented as a byte [0, 255].
     *
     * @return array{hue:float, saturation:float, lightness:float, alpha:int} Array of color components as numbers.
     */
    public function toHsla(): array
    {
        $hsla          = self::rgbToHsl($this->red, $this->green, $this->blue);
        $hsla['alpha'] = $this->alpha;
        return $hsla;
    }

    /**
     * Get the color as an array, with RGBA, HSL, and some other useful properties.
     *
     * @return array An array of color properties.
     */
    public function toArray(): array
    {
        return array_merge(
            $this->toRgba(),
            $this->toHsla(),
            [
                'relativeLuminance'  => $this->relativeLuminance,
                'perceivedLightness' => $this->perceivedLightness,
                'hex'                => $this->toHexString(),
                'bestTextColor'      => $this->bestTextColor()->toHexString(),
            ]
        );
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Color utilities

    /**
     * Checks if two colors are equal.
     *
     * @param self $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->_rgba === $other->_rgba;
    }

    /**
     * Transfer function for gamma correction.
     *
     * This is needed by the relative luminance calculation, but let's make it public and static in case people need
     * it for other purposes.
     *
     * @see https://en.wikipedia.org/wiki/SRGB#Transfer_function_(%22gamma%22)
     *
     * @param int $byte The RGB byte value.
     * @return float The transfer function value.
     */
    public function gamma(int $byte): float {
        // Convert RGB byte to a float in the range [0.0, 1.0].
        $c = $byte / 255.0;

        // Compute transfer function.
        return ($c <= 0.04045) ? ($c / 12.92) : ((($c + 0.055) / 1.055) ** 2.4);
    }

    /**
     * Determine the contrast ratio (as per WCAG 2.x).
     *
     * @param Color $other The other color to compare to.
     * @return float The contrast ratio.
     */
    public function contrastRatio(Color $other): float
    {
        $l1 = max($this->relativeLuminance, $other->relativeLuminance);
        $l2 = min($this->relativeLuminance, $other->relativeLuminance);
        return ($l1 + 0.05) / ($l2 + 0.05);
    }

    /**
     * Pick black or white text for the best contrast on this background color.
     *
     * @return Color The best text color.
     */
    public function bestTextColor(): Color
    {
        $black = new self('black');
        $white = new self('white');
        return ($black->contrastRatio($this) >= $white->contrastRatio($this)) ? $black : $white;
    }

    /**
     * Mix two colors.
     *
     * If the fraction is 0 (or very close to), then the result will equal the "other" color.
     * If the fraction is 1 (or very close to), then the result will equal the "this" color.
     * If the fraction isn't specified, then the result will have 50% of each color.
     *
     * @param self $other
     * @param float $frac The fraction of the "this" color as a float in the range [0.0, 1.0].
     * @return self
     */
    public function mix(Color $other, float $frac = 0.5): self
    {
        // Validate the fraction.
        self::_validateFrac($frac);

        // Check for 100% this color.
        if ($frac >= 1.0 - self::DELTA) {
            return clone $this;
        }
        // Check for 0% this color.
        if ($frac <= self::DELTA) {
            return clone $other;
        }

        // Compute the components of the new color.
        $frac2 = 1.0 - $frac;
        $r     = (int)round(($this->red * $frac) + ($other->red * $frac2));
        $g     = (int)round(($this->green * $frac) + ($other->green * $frac2));
        $b     = (int)round(($this->blue * $frac) + ($other->blue * $frac2));
        $a     = (int)round(($this->alpha * $frac) + ($other->alpha * $frac2));

        // Create and return the mixed color.
        return self::fromRgba($r, $g, $b, $a);
    }

    /**
     * Get the complementary color.
     *
     * @return self The complementary color.
     */
    public function complement(): self
    {
        $hsl = $this->toHsla();
        return self::fromHsla($hsl['hue'] + 180, $hsl['saturation'], $hsl['lightness'], $hsl['alpha']);
    }

    /**
     * Find the average of several colors.
     *
     * @static
     * @param Color ...$colors
     * @return self
     */
    public static function average(self ...$colors): self
    {
        // Get the number of colors and make sure we have at least one.
        $n = count($colors);
        if ($n === 0) {
            throw new ValueError('At least one color must be provided.');
        }

        // If there's only one, return it.
        if ($n === 1) {
            return $colors[0];
        }

        // Sum the color components.
        $sum_r = 0;
        $sum_g = 0;
        $sum_b = 0;
        $sum_a = 0;
        foreach ($colors as $color) {
            $sum_r += $color->red;
            $sum_g += $color->green;
            $sum_b += $color->blue;
            $sum_a += $color->alpha;
        }

        // Calculate the averages.
        $avg = fn($sum) => (int)round($sum / (float)$n);
        $r   = $avg($sum_r);
        $g   = $avg($sum_g);
        $b   = $avg($sum_b);
        $a   = $avg($sum_a);

        // Create and return the average color.
        return self::fromRgba($r, $g, $b, $a);
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Private instance helper methods

    /**
     * Set the red, green, blue, and alpha components all at the same time.
     *
     * NB: This is an internal function, and arguments are assumed to be in range for bytes.
     *
     * @param int $red The red component as a byte.
     * @param int $green The green component as a byte.
     * @param int $blue The blue component as a byte.
     * @param int $alpha The alpha value as a byte.
     */
    private function _setRgbaBytes(int $red, int $green, int $blue, int $alpha): void
    {
        $this->_rgba = chr($red) . chr($green) . chr($blue) . chr($alpha);
    }

    /**
     * Sets the RGB components of this color from HSL values, preserving the current alpha.
     * This is an internal function and arguments are assumed to be valid.
     *
     * @param float $h The hue in degrees (0.0–360.0).
     * @param float $s The saturation as a fraction (0.0–1.0).
     * @param float $l The lightness as a fraction (0.0–1.0).
     */
    private function _setRgbaBytesFromHsl(float $h, float $s, float $l): void
    {
        ['red' => $r, 'green' => $g, 'blue' => $b] = self::hslToRgb($h, $s, $l);
        $this->_setRgbaBytes($r, $g, $b, $this->alpha);
    }

    /**
     * Get a byte value from the internal color string.
     *
     * NB: This is an internal function, and the offset is assumed to be in range [0, 3].
     *
     * @param int $offset The offset, which will be 0 for red, 1 for green, 2 for blue, and 3 for alpha.
     * @return int The byte value at the given position.
     */
    private function _getByte(int $offset): int
    {
        return ord($this->_rgba[$offset]);
    }

    /**
     * Set a color component value within the internal color string.
     *
     * NB: This is an internal function, and the offset is assumed to be in range [0, 3].
     *
     * @param int $offset The offset, which will be 0 for red, 1 for green, 2 for blue, and 3 for alpha.
     * @param int $byte The color component value.
     * @return void
     * @throws ValueError If the provided valid isn't valid.
     */
    private function _setByte(int $offset, int $byte): void
    {
        // Check valid byte range.
        self::_validateByte($byte);

        // Update the color value.
        $this->_rgba[$offset] = chr($byte);
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Methods for converting a Color to a string

    /**
     * Convert an angle to a string suitable for CSS.
     *
     * @param float $angle The angle to format.
     * @return string The formatted angle string.
     */
    private static function _angleToString(float $angle): string
    {
        return round($angle, 6) . 'deg';
    }

    /**
     * Format a percentage.
     *
     * @param float $frac The fraction to format as a percentage.
     * @return string The formatted percentage string.
     */
    private static function _fracToPercentString(float $frac): string
    {
        return round($frac * 100, 6) . '%';
    }

    /**
     * Format a byte value as a fraction.
     *
     * @param int $byte The byte value to format.
     * @return string The byte formatted as a fraction.
     */
    private static function _byteToFracString(int $byte): string
    {
        return (string)round(self::_byteToFrac($byte), 6);
    }

    /**
     * Outputs the color as a 6-digit hexadecimal string, or 8-digit if alpha is included.
     *
     * @param bool $include_alpha If the 2 characters for the alpha byte should be included.
     * @param bool $include_hash If the result should have a leading '#'.
     * @param bool $upper_case If letter digits should be upper-case.
     * @return string The color formatted as a CSS hexadecimal color string.
     */
    public function toHexString(bool $include_alpha = true, bool $include_hash = true, bool $upper_case = false): string
    {
        // Convert the 4-byte binary string to an 8-character hexadecimal string.
        $hex = bin2hex($this->_rgba);

        // Remove the last 2 characters if alpha isn't required.
        if (!$include_alpha) {
            $hex = substr($hex, 0, 6);
        }

        // Convert to upper-case if required.
        if ($upper_case) {
            $hex = strtoupper($hex);
        }

        // Add a leading '#' if required.
        return $include_hash ? "#$hex" : $hex;
    }

    /**
     * Outputs the color as an RGB CSS string.
     *
     * @return string The Color as a CSS-compatible rgb color string, e.g. "rgb(120, 50, 50)".
     */
    public function toRgbString(): string
    {
        return "rgb($this->red, $this->green, $this->blue)";
    }

    /**
     * Outputs the color as an RGBA CSS string.
     *
     * @return string The Color as a CSS-compatible rgba color string, e.g. "rgba(120, 50, 50, 0.5)".
     */
    public function toRgbaString(): string
    {
        $a = self::_byteToFracString($this->alpha);
        return "rgba($this->red, $this->green, $this->blue, $a)";
    }

    /**
     * Outputs the color as an HSL CSS string.
     *
     * @return string The Color as a CSS-compatible hsl color string, e.g. "hsl(120deg, 50%, 50%)".
     */
    public function toHslString(): string
    {
        $hsla = $this->toHsla();
        $h    = self::_angleToString($hsla['hue']);
        $s    = self::_fracToPercentString($hsla['saturation']);
        $l    = self::_fracToPercentString($hsla['lightness']);
        return "hsl($h, $s, $l)";
    }

    /**
     * Outputs the color as an HSLA CSS string.
     *
     * @return string The Color as a CSS-compatible hsla color string, e.g. "hsla(120deg, 50%, 50%, 0.5)".
     */
    public function toHslaString(): string
    {
        $hsl = $this->toHsla();
        $h   = self::_angleToString($hsl['hue']);
        $s   = self::_fracToPercentString($hsl['saturation']);
        $l   = self::_fracToPercentString($hsl['lightness']);
        $a   = self::_byteToFracString($hsl['alpha']);
        return "hsla($h, $s, $l, $a)";
    }

    /**
     * Stringable implementation.
     *
     * The default string representation of a Color is an 8-digit RGBA lower-case hex string with leading '#'.
     * Suitable for use in CSS.
     *
     * @return string The Color as a CSS hexadecimal color string (RGBA, 8 digits).
     */
    public function __toString(): string
    {
        return $this->toHexString();
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Static methods for converting between RGB and HSL.

    /**
     * Convert RGB values to HSL.
     *
     * Algorithm:
     * @see https://en.wikipedia.org/wiki/HSL_and_HSV#From_RGB
     *
     * @static
     * @param int $red The red byte value.
     * @param int $green The green byte value.
     * @param int $blue The blue byte value.
     * @return array{hue:float, saturation:float, lightness:float} Array of floats with HSL values:
     *   hue        => [0, 360)
     *   saturation => [0.0, 1.0]
     *   lightness  => [0.0, 1.0]
     */
    public static function rgbToHsl(int $red, int $green, int $blue): array
    {
        // Check the red, green and blue bytes are in range:
        self::_validateByte($red);
        self::_validateByte($green);
        self::_validateByte($blue);

        // Convert to fractions.
        $r = self::_byteToFrac($red);
        $g = self::_byteToFrac($green);
        $b = self::_byteToFrac($blue);

        // Get the min and max values.
        $min = min($r, $g, $b);
        $max = max($r, $g, $b);
        $c   = $max - $min;
        $l   = ($min + $max) / 2;

        // Check for gray.
        if ($c === 0.0) {
            $h = 0.0;
            $s = 0.0;
        }
        else {
            // Calculate hue.
            if ($max === $r) {
                $h = fmod(($g - $b) / $c, 6);
            }
            elseif ($max === $g) {
                $h = ($b - $r) / $c + 2;
            }
            else { // $max === $b
                $h = ($r - $g) / $c + 4;
            }
            // Wrap hue to [0, 360).
            $h = Angle::wrapDegrees($h * 60);

            // Calculate saturation.
            if ($l <= 0.0 || $l >= 1.0) {
                $s = 0.0;
            }
            else {
                $s = $c / (1 - abs(2 * $l - 1));
            }
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
     * @static
     * @param float $hue The hue as an angle in degrees.
     * @param float $saturation The saturation as a fraction in the range [0.0, 1.0].
     * @param float $lightness The lightness as a fraction in the range [0.0, 1.0].
     * @return array{red:int, green:int, blue:int} An array of red, green, and blue bytes.
     */
    public static function hslToRgb(float $hue, float $saturation, float $lightness): array
    {
        // Ensure all values are in the desired ranges.
        $hue = Angle::wrapDegrees($hue);
        self::_validateFrac($saturation);
        self::_validateFrac($lightness);

        // Conversion function.
        $f = function ($n) use ($hue, $saturation, $lightness): int {
            $k = fmod($n + $hue / 30, 12);
            $a = $saturation * min($lightness, 1 - $lightness);
            $c = $lightness - $a * max(-1, min($k - 3, 9 - $k, 1));
            return self::_fracToByte($c);
        };

        return [
            'red'   => $f(0),
            'green' => $f(8),
            'blue'  => $f(4)
        ];
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Static members for working with hex strings and named colors

    /**
     * Convert a color name to an 8-digit hex value (no leading '#').
     *
     * @static
     * @param string $name A CSS color name.
     * @return string The hex value for this color.
     * @throws ValueError if the provided string is not a valid color name.
     */
    public static function colorNameToHex(string $name): string
    {
        $name = trim(strtolower($name));

        // Check the provided color name is valid.
        if (!self::isValidColorName($name)) {
            throw new ValueError("Invalid color name '$name'.");
        }

        // Look up the hex value for the color.
        return self::CSS_COLOR_NAMES[$name];
    }

    /**
     * Returns true if the string is a valid hex color string.
     * A leading '#' is optional, and there can be 3, 4, 6, or 8 hex digits.
     *
     * @static
     * @param string $str A string that could be a CSS hex color.
     * @return bool If the provided string is a valid CSS hex color string.
     */
    public static function isValidHexString(string $str): bool
    {
        $str = trim($str);

        // Reject empty strings.
        if ($str === '') {
            return false;
        }

        // Get the length and return early if longer than the maximum valid length.
        $len = strlen($str);
        if ($len > 9) {
            return false;
        }

        // Trim the leading '#' if present.
        if ($str[0] === '#') {
            $str = substr($str, 1);
            $len--;
        }

        // Check the string is all hex digits and its length is 3, 4, 6 or 8.
        return ctype_xdigit($str) && in_array($len, [3, 4, 6, 8], true);
    }

    /**
     * Check if a given string is a valid CSS color name.
     *
     * @static
     * @param string $name A color name.
     * @return bool If the name is a valid CSS color name.
     */
    public static function isValidColorName(string $name): bool
    {
        return isset(self::CSS_COLOR_NAMES[strtolower($name)]);
    }

    /**
     * Check if a given string is a valid CSS hex color string or color name.
     *
     * @static
     * @param string $name A CSS hex color or color name.
     * @return bool If the name is a valid CSS color name.
     */
    public static function isValidColorString(string $name): bool
    {
        return self::isValidColorName($name) || self::isValidHexString($name);
    }

    /**
     * Convert a CSS color hex string, being 3, 4, 6, or 8 hexadecimal digits, lower- or upper-case, with or without
     * leading '#', into a canonical form, which is defined as 8 lower-case hexadecimal digits with no leading '#'.
     *
     * @param string $hex
     * @return string
     */
    public static function normalizeHex(string $hex): string
    {
        // Check the input string is a valid format.
        if (!self::isValidHexString($hex)) {
            throw new ValueError("The provided string '$hex' is not a valid CSS hexadecimal color string.");
        }

        // Remove a leading # character if present and lower-case letter digits.
        $hex = strtolower(ltrim(trim($hex), '#'));

        // Check length.
        $len = strlen($hex);
        if ($len === 8) {
            return $hex;
        }

        // Expand string to 8 characters as needed.
        $rgb = $len === 6 ? $hex : ($hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2]);
        $a   = $len === 4 ? ($hex[3] . $hex[3]) : 'ff';
        return $rgb . $a;
    }

    /**
     * Convert a hex string in CSS form to 4 RGBA bytes
     *
     * The allowed values can have an optional leading '#' followed by 3, 4, 6, or 8 lower- or upper-case hex digits.
     * If there are only 3 or 4 digits, the value is expanded to 6 or 8 digits respectively by repeating each digit.
     *
     * @param string $hex A CSS hex color string.
     * @return array{red:int, green:int, blue:int, alpha:int} Array of color components as bytes.
     * @throws ValueError if the provided string is not a valid CSS hex color string.
     */
    public static function hexStringToBytes(string $hex): array
    {
        // Check the input string is a valid format.
        if (!self::isValidHexString($hex)) {
            throw new ValueError("The provided string '$hex' is not a valid CSS hexadecimal color string.");
        }

        // Normalize to 8 hex digits.
        $hex = self::normalizeHex($hex);

        // Convert string into bytes.
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $a = hexdec(substr($hex, 6, 2));

        return [
            'red'   => $r,
            'green' => $g,
            'blue'  => $b,
            'alpha' => $a
        ];
    }

    /**
     * Convert a color name to an 8-digit hex value (no leading '#').
     *
     * @static
     * @param string $name A CSS color name.
     * @return array{red:int, green:int, blue:int, alpha:int} Array of color components as bytes.
     * @throws ValueError if the provided string is not a valid color name.
     */
    public static function colorNameToBytes(string $name): array
    {
        $name = trim(strtolower($name));

        // Check the provided color name is valid.
        if (!self::isValidColorName($name)) {
            throw new ValueError("Invalid color name '$name'.");
        }

        // Look up the hex value for the color.
        $hex = self::CSS_COLOR_NAMES[$name];

        // Convert the hex string to RGBA bytes.
        return self::hexStringToBytes($hex);
    }

    /**
     * Convert a CSS hex or named color to RGBA bytes.
     *
     * @static
     * @param string $str The color string.
     * @return array{red:int, green:int, blue:int, alpha:int} Array of color components as bytes.
     * @throws ValueError if the provided string is not a valid CSS color name or hex color string.
     */
    public static function colorStringToBytes(string $str): array
    {
        $str = trim($str);

        // Convert a color name to a hex string.
        if (self::isValidColorName($str)) {
            $str = self::colorNameToHex($str);
        }

        // Convert a hex string to RGBA bytes.
        return self::hexStringToBytes($str);
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Validation and conversion methods

    /**
     * Convert a float to a value within the range [0.0, 1.0].
     *
     * @param float $value The value to clamp.
     * @return float The clamped value.
     */
    private static function _clamp(float $value): float
    {
        return max(0.0, min($value, 1.0));
    }

    /**
     * Check if a byte value is valid.
     *
     * @param int $byte The value to check.
     * @return void
     * @throws ValueError If the byte is out of range.
     */
    private static function _validateByte(int $byte): void
    {
        if ($byte < 0 || $byte > 255) {
            throw new ValueError("Invalid byte value. Byte values must be in the range [0, 255].");
        }
    }

    /**
     * Check if a fraction value is valid.
     *
     * @param float $frac The fraction to check.
     * @return void
     * @throws ValueError If the byte is out of range.
     */
    private static function _validateFrac(float $frac): void
    {
        if ($frac < 0 || $frac > 1) {
            throw new ValueError("Invalid fraction value. Fractions must be in the range [0.0, 1.0].");
        }
    }

    /**
     * Convert a byte to a fraction.
     *
     * @static
     * @param int $byte The byte value.
     * @return float The fraction.
     */
    private static function _byteToFrac(int $byte): float
    {
        return self::_clamp($byte / 255.0);
    }

    /**
     * Convert a fraction to a byte.
     *
     * @static
     * @param float $frac The fraction to convert.
     * @return int The byte value.
     */
    private static function _fracToByte(float $frac): int
    {
        return (int)round(self::_clamp($frac) * 255.0);
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region CSS color names

    /**
     * Array of CSS color names.
     *
     * @var array<string,string>
     */
    private const array CSS_COLOR_NAMES = [
        'transparent'          => '00000000',
        'aliceblue'            => 'f0f8ffff',
        'amethyst'             => '9966ccff',
        'antiquewhite'         => 'faebd7ff',
        'aqua'                 => '00ffffff',
        'aquamarine'           => '7fffd4ff',
        'azure'                => 'f0ffffff',
        'beige'                => 'f5f5dcff',
        'bisque'               => 'ffe4c4ff',
        'black'                => '000000ff',
        'blanchedalmond'       => 'ffebcdff',
        'blue'                 => '0000ffff',
        'blueviolet'           => '8a2be2ff',
        'brown'                => 'a52a2aff',
        'burlywood'            => 'deb887ff',
        'cadetblue'            => '5f9ea0ff',
        'chartreuse'           => '7fff00ff',
        'chocolate'            => 'd2691eff',
        'coral'                => 'ff7f50ff',
        'cornflowerblue'       => '6495edff',
        'cornsilk'             => 'fff8dcff',
        'crimson'              => 'dc143cff',
        'cyan'                 => '00ffffff',
        'darkblue'             => '00008bff',
        'darkcyan'             => '008b8bff',
        'darkgoldenrod'        => 'b8860bff',
        'darkgray'             => 'a9a9a9ff',
        'darkgreen'            => '006400ff',
        'darkgrey'             => 'a9a9a9ff',
        'darkkhaki'            => 'bdb76bff',
        'darkmagenta'          => '8b008bff',
        'darkolivegreen'       => '556b2fff',
        'darkorange'           => 'ff8c00ff',
        'darkorchid'           => '9932ccff',
        'darkred'              => '8b0000ff',
        'darksalmon'           => 'e9967aff',
        'darkseagreen'         => '8fbc8fff',
        'darkslateblue'        => '483d8bff',
        'darkslategray'        => '2f4f4fff',
        'darkslategrey'        => '2f4f4fff',
        'darkturquoise'        => '00ced1ff',
        'darkviolet'           => '9400d3ff',
        'deeppink'             => 'ff1493ff',
        'deepskyblue'          => '00bfffff',
        'dimgray'              => '696969ff',
        'dimgrey'              => '696969ff',
        'dodgerblue'           => '1e90ffff',
        'firebrick'            => 'b22222ff',
        'floralwhite'          => 'fffaf0ff',
        'forestgreen'          => '228b22ff',
        'fuchsia'              => 'ff00ffff',
        'gainsboro'            => 'dcdcdcff',
        'ghostwhite'           => 'f8f8ffff',
        'gold'                 => 'ffd700ff',
        'goldenrod'            => 'daa520ff',
        'gray'                 => '808080ff',
        'green'                => '008000ff',
        'greenyellow'          => 'adff2fff',
        'grey'                 => '808080ff',
        'honeydew'             => 'f0fff0ff',
        'hotpink'              => 'ff69b4ff',
        'indianred'            => 'cd5c5cff',
        'indigo'               => '4b0082ff',
        'ivory'                => 'fffff0ff',
        'khaki'                => 'f0e68cff',
        'lavender'             => 'e6e6faff',
        'lavenderblush'        => 'fff0f5ff',
        'lawngreen'            => '7cfc00ff',
        'lemonchiffon'         => 'fffacdff',
        'lightblue'            => 'add8e6ff',
        'lightcoral'           => 'f08080ff',
        'lightcyan'            => 'e0ffffff',
        'lightgoldenrodyellow' => 'fafad2ff',
        'lightgray'            => 'd3d3d3ff',
        'lightgreen'           => '90ee90ff',
        'lightgrey'            => 'd3d3d3ff',
        'lightpink'            => 'ffb6c1ff',
        'lightsalmon'          => 'ffa07aff',
        'lightseagreen'        => '20b2aaff',
        'lightskyblue'         => '87cefaff',
        'lightslategray'       => '778899ff',
        'lightslategrey'       => '778899ff',
        'lightsteelblue'       => 'b0c4deff',
        'lightyellow'          => 'ffffe0ff',
        'lime'                 => '00ff00ff',
        'limegreen'            => '32cd32ff',
        'linen'                => 'faf0e6ff',
        'magenta'              => 'ff00ffff',
        'maroon'               => '800000ff',
        'mediumaquamarine'     => '66cdaaff',
        'mediumblue'           => '0000cdff',
        'mediumorchid'         => 'ba55d3ff',
        'mediumpurple'         => '9370dbff',
        'mediumseagreen'       => '3cb371ff',
        'mediumslateblue'      => '7b68eeff',
        'mediumspringgreen'    => '00fa9aff',
        'mediumturquoise'      => '48d1ccff',
        'mediumvioletred'      => 'c71585ff',
        'midnightblue'         => '191970ff',
        'mintcream'            => 'f5fffaff',
        'mistyrose'            => 'ffe4e1ff',
        'moccasin'             => 'ffe4b5ff',
        'navajowhite'          => 'ffdeadff',
        'navy'                 => '000080ff',
        'oldlace'              => 'fdf5e6ff',
        'olive'                => '808000ff',
        'olivedrab'            => '6b8e23ff',
        'orange'               => 'ffa500ff',
        'orangered'            => 'ff4500ff',
        'orchid'               => 'da70d6ff',
        'palegoldenrod'        => 'eee8aaff',
        'palegreen'            => '98fb98ff',
        'paleturquoise'        => 'afeeeeff',
        'palevioletred'        => 'db7093ff',
        'papayawhip'           => 'ffefd5ff',
        'peachpuff'            => 'ffdab9ff',
        'peru'                 => 'cd853fff',
        'pink'                 => 'ffc0cbff',
        'plum'                 => 'dda0ddff',
        'powderblue'           => 'b0e0e6ff',
        'purple'               => '800080ff',
        'red'                  => 'ff0000ff',
        'rosybrown'            => 'bc8f8fff',
        'royalblue'            => '4169e1ff',
        'saddlebrown'          => '8b4513ff',
        'salmon'               => 'fa8072ff',
        'sandybrown'           => 'f4a460ff',
        'seagreen'             => '2e8b57ff',
        'seashell'             => 'fff5eeff',
        'sienna'               => 'a0522dff',
        'silver'               => 'c0c0c0ff',
        'skyblue'              => '87ceebff',
        'slateblue'            => '6a5acdff',
        'slategray'            => '708090ff',
        'slategrey'            => '708090ff',
        'snow'                 => 'fffafaff',
        'springgreen'          => '00ff7fff',
        'steelblue'            => '4682b4ff',
        'tan'                  => 'd2b48cff',
        'teal'                 => '008080ff',
        'thistle'              => 'd8bfd8ff',
        'tomato'               => 'ff6347ff',
        'turquoise'            => '40e0d0ff',
        'violet'               => 'ee82eeff',
        'wheat'                => 'f5deb3ff',
        'white'                => 'ffffffff',
        'whitesmoke'           => 'f5f5f5ff',
        'yellow'               => 'ffff00ff',
        'yellowgreen'          => '9acd32ff',
    ];

    // endregion
}
