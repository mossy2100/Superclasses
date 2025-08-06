<?php

declare(strict_types=1);

namespace Superclasses;

/**
 * Color class.
 *
 * @author  Shaun Moss
 * @version 2012-08-11
 *
 * @todo TEST THIS CLASS!
 *
 * @idea Also provide properties for cyan, magenta, yellow and black.
 */
class Color
{

  /**
   * The color value, a 32-bit integer with bytes organised as ARGB.
   *
   * @var int
   */
  protected $value;

  /**
   * Constructor.  Multiple function signatures supported.
   *
   * General pattern:
   *   new Color();
   *   new Color($color);
   *   new Color($color, $alpha);
   *   new Color($red, $green, $blue);
   *   new Color($red, $green, $blue, $alpha);
   *   new Color($hue, $saturation, $lightness, TRUE);
   *   new Color($hue, $saturation, $lightness, $alpha, TRUE);
   *
   * $color can be:
   *   - a hex color string (3 or 6-digit, with or without leading '#')
   *   - a color name or 'transparent'
   *   - a 24-bit RGB integer, as would be returned from imagecolorat()
   *   - a Color object to clone
   *   - CSS-style color string of the form:
   *     - 'rgb(red, green, blue)'
   *     - 'rgba(red, green, blue, alpha)'
   *     - 'hsl(hue, saturation, lightness)'
   *     - 'hsla(hue, saturation, lightness, alpha)'
   *
   * $red, $green and $blue can be:
   *   - integer from 0..255
   *   - string from 0%..100%
   *
   * $alpha, $saturation and $lightness can be:
   *   - float from 0.0..1.0
   *   - string from 0%..100%
   *
   * $hue can be any float, which will be normalized to the range [0..360)
   *
   * NOTE if alpha is defined in both the first and second params, they will be multiplied.
   *
   * Examples:
   *
   * Hex #rrggbb:
   *   new Color('#ABCDEF');
   *   new Color('#ABCDEF', 0.75);
   *   new Color('#ABCDEF', '75%');
   *   new Color('ABCDEF');
   *   new Color('ABCDEF', 0.75);
   *   new Color('ABCDEF', '75%');
   *
   * Hex #rgb:
   *   new Color('#ABC');
   *   new Color('#ABC', 0.75);
   *   new Color('#ABC', '75%');
   *   new Color('ABC');
   *   new Color('ABC', 0.75);
   *   new Color('ABC', '75%');
   *
   * Named colors:
   *   new Color('Orange');
   *   new Color('Orange', 0.75);
   *   new Color('Orange', '75%');
   *   new Color('Transparent');
   *
   * 24-bit color integer:
   *   new Color(0xABCDEF);
   *   new Color(0xABCDEF, 0.75);
   *   new Color(0xABCDEF, '75%');
   *   new Color(imagecolorat($image, $x, $y));
   *   new Color(imagecolorat($image, $x, $y), 0.75);
   *   new Color(imagecolorat($image, $x, $y), '75%');
   *
   * Clone:
   *   new Color($otherColorObject);
   *   new Color($otherColorObject, 0.75);
   *   new Color($otherColorObject, '75%');
   *
   * RGBA:
   *   new Color(100, 200, 150);
   *   new Color(100, 200, 150, 0.75);
   *   new Color(100, 200, 150, '75%');
   *   new Color('30%', '50%', '40%');
   *   new Color('30%', '50%', '40%', 0.75);
   *   new Color('30%', '50%', '40%', '75%');
   *
   * HSLA:
   *   new Color(120, '80%', '30%', TRUE);
   *   new Color(120, '80%', '30%', 0.75, TRUE);
   *   new Color(120, '80%', '30%', '75%', TRUE);
   *
   * CSS :
   *   new Color('rgb(100, 200, 150)');
   *   new Color('rgba(100, 200, 150, 0.75)');
   *   new Color('rgb(30%, 90%, 12%)');
   *   new Color('rgba(30%, 90%, 12%, 0.75)');
   *   new Color('hsl(120, 59%, 72%)');
   *   new Color('hsla(120, 59%, 72%, 0.75)');
   *
   * Array:
   *   new Color(array('red' => 100, 'green' => 200, 'blue' => 150));
   *   new Color(array('red' => 100, 'green' => 200, 'blue' => 150), 0.75);
   *   new Color(array('red' => 100, 'green' => 200, 'blue' => 150), '75%');
   *   new Color(array('red' => 100, 'green' => 200, 'blue' => 150, 'alpha' => 0.75));
   *
   * @param string|int|float|Color $param1
   *   Color string or int, Color, red, or hue
   * @param string|int|float $param2
   *   Alpha, green or saturation
   * @param string|int|float $param3
   *   Blue or lightness
   * @param string|float|bool $param4
   *   Alpha value or FALSE for RGB, TRUE for HSL
   * @param bool $param5
   *   TRUE for HSL, FALSE for RGB (default)
   */
  public function __construct($param1 = NULL, $param2 = NULL, $param3 = NULL, $param4 = NULL, $param5 = FALSE)
  {
    $n_args = func_num_args();

    if ($n_args == 0) {
      // Default to fully-opaque black:
      $this->rgba(0, 0, 0, 1);
      return;
    }

    if ($n_args <= 2) {

      // Second param is alpha, default to 1:
      $alpha = self::normalizeFraction($param2);

      if (is_numeric($param1)) {
        // Integer representing a 24-bit RGB color, as would be supplied by imagecolorat().
        $this->value = (((int) $param1) & 0xFFFFFF) << 8;
        $this->alpha($alpha);
        return;
      }

      if (is_string($param1)) {

        // Transparent:
        if (strcasecmp($param1, 'transparent') == 0) {
          // Use same value as CSS for transparent. Alpha is 0, color is black by default.
          $this->value = 0;
          return;
        }

        // Check for color name:
        if (self::isColorName($param1)) {
          $rgb = self::hex2rgb(self::colorName2hex($param1));
          $this->rgba($rgb['red'], $rgb['green'], $rgb['blue'], $alpha);
          return;
        }

        // Check for hex color string:
        if (self::isHexString($param1)) {
          $rgb = self::hex2rgb($param1);
          $this->rgba($rgb['red'], $rgb['green'], $rgb['blue'], $alpha);
          return;
        }

        // Regular expressions:
        $num = "((\d*\.)?\d+)";
        $pc = "\s*($num%?)\s*";
        $deg = "\s*(-?$num)\s*";

        // Check for rgb():
        $pattern = "/^rgb\($pc,$pc,$pc\)$/i";
        $n_matches = preg_match($pattern, $param1, $matches);
        if ($n_matches) {
          $this->rgba($matches[1], $matches[4], $matches[7], $alpha);
          return;
        }

        // Check for rgba():
        $pattern = "/^rgba\($pc,$pc,$pc,$pc\)$/i";
        $n_matches = preg_match($pattern, $param1, $matches);
        if ($n_matches) {
          $alpha *= self::normalizeFraction($matches[10]);
          $this->rgba($matches[1], $matches[4], $matches[7], $alpha);
          return;
        }

        // Check for hsl():
        $pattern = "/^hsl\($deg,$pc,$pc\)$/i";
        $n_matches = preg_match($pattern, $param1, $matches);
        if ($n_matches) {
          $this->hsla($matches[1], $matches[4], $matches[7], $alpha);
          return;
        }

        // Check for hsla():
        $pattern = "/^hsla\($deg,$pc,$pc,$pc\)$/i";
        $n_matches = preg_match($pattern, $param1, $matches);
        if ($n_matches) {
          $alpha *= self::normalizeFraction($matches[10]);
          $this->hsla($matches[1], $matches[4], $matches[7], $alpha);
          return;
        }
      }

      if (is_array($param1)) {
        // RGB or RGBA array.
        $red    = isset($param1['red'])   ? $param1['red']   : 0;
        $green  = isset($param1['green']) ? $param1['green'] : 0;
        $blue   = isset($param1['blue'])  ? $param1['blue']  : 0;
        $alpha2 = isset($param1['alpha']) ? $param1['alpha'] : 1;
        $this->rgba($red, $green, $blue, $alpha * $alpha2);
        return;
      }

      if ($param1 instanceof self) {
        // Copy the object, taking into account the alpha parameter if provided:
        $rgba = $param1->rgba();
        $this->rgba($rgba['red'], $rgba['green'], $rgba['blue'], $rgba['alpha'] * $alpha);
        return;
      }
    } elseif ($n_args >= 3) {

      if ($n_args == 3) {
        // RGB with no alpha:
        $alpha = 1;
        $hsl = FALSE;
      } elseif ($n_args == 4) {
        if (is_bool($param4)) {
          // HSL or RGB with unspecified alpha, default to 1:
          $alpha = 1;
          $hsl = $param4;
        } else {
          // RGB with alpha:
          $alpha = (float) $param4;
          $hsl = FALSE;
        }
      } else {
        // HSL or RGB with alpha:
        $alpha = (float) $param4;
        $hsl = (bool) $param5;
      }

      if ($hsl) {
        $this->hsla($param1, $param2, $param3, $alpha);
      } else {
        $this->rgba($param1, $param2, $param3, $alpha);
      }
      return;
    }

    trigger_error("Color::__construct() - Invalid parameters.", E_USER_WARNING);
  }

  ///////////////////////////////////////////////////////////////////////////
  // Methods for getting/setting properties.

  /**
   * Gets/sets the 32-bit RGBA color value.
   *
   * @param null|int $value
   *   0..0xFFFFFFFF
   * @return int|null
   *   0..0xFFFFFFFF
   */
  public function value($value = NULL)
  {
    if ($value === NULL) {
      // Get the value:
      return $this->value;
    }

    // Set the value. Convert to 32-bit int:
    $this->value = ((int) $value) & 0xFFFFFFFF;

    return $this;
  }

  /**
   * Gets/sets the red value.
   *
   * @param null|int|string $red
   *   0..255 or 0%..100%
   * @return int|null
   *   0..255
   */
  public function red($red = NULL)
  {
    if ($red === NULL) {
      // Get the red value:
      return ($this->value >> 24) & 0xFF;
    }

    // Set the red value:
    $red = self::normalizeByte($red);
    $this->value = ($this->value & 0x00FFFFFF) | ($red << 24);

    return $this;
  }

  /**
   * Gets/sets the green value.
   *
   * @param null|int|string $green
   *   0..255 or 0%..100%
   * @return int|null
   *   0..255
   */
  public function green($green = NULL)
  {
    if ($green === NULL) {
      // Get the green value:
      return ($this->value >> 16) & 0xFF;
    }

    // Set the green value:
    $green = self::normalizeByte($green);
    $this->value = ($this->value & 0xFF00FFFF) | ($green << 16);

    return $this;
  }

  /**
   * Gets/sets the blue value.
   *
   * @param null|int|string $blue
   *   0..255 or 0%..100%
   * @return int|null
   *   0..255
   */
  public function blue($blue = NULL)
  {
    if ($blue === NULL) {
      // Get the blue value:
      return ($this->value >> 8) & 0xFF;
    }

    // Set the blue value:
    $blue = self::normalizeByte($blue);
    $this->value = ($this->value & 0xFFFF00FF) | ($blue << 8);

    return $this;
  }

  /**
   * Gets/sets the alpha value.
   *
   * @param null|float|string $alpha
   *   0.0..1.0 or 0%..100%
   * @return float|null
   *   0.0..1.0
   */
  public function alpha($alpha = NULL)
  {
    if ($alpha === NULL) {
      // Get the alpha value as a fraction in the range 0.0..1.0:
      return ($this->value & 0xFF) / 255;
    }

    // Set the alpha value:
    $alpha = self::normalizeFractionByte($alpha);
    $this->value = ($this->value & 0xFFFFFF00) | $alpha;

    return $this;
  }

  /**
   * Gets/sets the hue.
   *
   * @param null|float $hue
   *   0..360
   * @return float|null
   *   0..360
   */
  public function hue($hue = NULL)
  {
    if ($hue === NULL) {
      // Get the hue:
      $hsl = $this->hsl();
      return $hsl['hue'];
    }

    // Set the hue:
    $hue = self::normalizeDegree($hue);
    $hsl = $this->hsl();
    $this->hsl($hue, $hsl['saturation'], $hsl['lightness']);

    return $this;
  }

  /**
   * Gets/sets the saturation.
   *
   * @param null|float|string $saturation
   *   0.0..1.0 or 0%..100%
   * @return float|null
   *   0.0..1.0
   */
  public function saturation($saturation = NULL)
  {
    if ($saturation === NULL) {
      // Get the saturation:
      $hsl = $this->hsl();
      return $hsl['saturation'];
    }

    // Set the saturation:
    $saturation = self::normalizeFraction($saturation);
    $hsl = $this->hsl();
    $this->hsl($hsl['hue'], $saturation, $hsl['lightness']);

    return $this;
  }

  /**
   * Gets/sets the lightness.
   *
   * @param null|float|string $lightness
   *   0.0..1.0 or 0%..100%
   * @return float|null
   *   0.0..1.0
   */
  public function lightness($lightness = NULL)
  {
    if ($lightness === NULL) {
      // Get the lightness:
      $hsl = $this->hsl();
      return $hsl['lightness'];
    }

    // Set the lightness:
    $lightness = self::normalizeFraction($lightness);
    $hsl = $this->hsla();
    $this->hsl($hsl['hue'], $hsl['saturation'], $lightness);

    return $this;
  }

  ///////////////////////////////////////////////////////////////////////////
  // Public methods for getting/setting multiple properties.

  /**
   * Gets/sets all RGBA values.
   *
   * @param null|int|string $red
   *   0..255 or 0%..100%
   * @param null|int|string $green
   *   0..255 or 0%..100%
   * @param null|int|string $blue
   *   0..255 or 0%..100%
   * @param null|float|string $alpha
   *   0.0..1.0 or 0%..100%
   * @return  array
   */
  public function rgba($red = NULL, $green = NULL, $blue = NULL, $alpha = NULL)
  {
    if ($red === NULL) {
      // Get the RGBA values:
      return array_merge($this->rgb(), array('alpha' => $this->alpha()));
    }

    // Set the RGBA values:
    $this->red($red);
    $this->green($green);
    $this->blue($blue);
    $this->alpha($alpha);

    return $this;
  }

  /**
   * Gets/sets all RGB values.
   *
   * @param null|int|string $red
   *   0..255 or 0%..100%
   * @param null|int|string $green
   *   0..255 or 0%..100%
   * @param null|int|string $blue
   *   0..255 or 0%..100%
   * @return  array
   */
  public function rgb($red = NULL, $green = NULL, $blue = NULL)
  {
    if ($red === NULL) {
      // Get the RGB values:
      return array(
        'red'   => $this->red(),
        'green' => $this->green(),
        'blue'  => $this->blue(),
      );
    }

    // Set the RGB values:
    $this->red($red);
    $this->green($green);
    $this->blue($blue);

    return $this;
  }

  /**
   * Gets/sets all HSLA values.
   *
   * @param null|float $hue
   *   0..360
   * @param null|float|string $saturation
   *   0.0..1.0 or 0%..100%
   * @param null|float|string $lightness
   *   0.0..1.0 or 0%..100%
   * @param null|float|string $alpha
   *   0.0..1.0 or 0%..100%
   * @return array|null
   *   hue        => 0..360
   *   saturation => 0.0..1.0
   *   lightness  => 0.0..1.0
   *   alpha      => 0.0..1.0
   */
  public function hsla($hue = NULL, $saturation = NULL, $lightness = NULL, $alpha = NULL)
  {
    if ($hue === NULL) {
      // Get the HSLA values:
      return array_merge($this->hsl(), array('alpha' => $this->alpha()));
    }

    // Set the HSLA values:
    $rgb = self::hsl2rgb($hue, $saturation, $lightness);
    $this->rgba($rgb['red'], $rgb['green'], $rgb['blue'], $alpha);

    return $this;
  }

  /**
   * Gets/sets all HSL values.
   *
   * @param null|float $hue
   *   0..360
   * @param null|float|string $saturation
   *   0.0..1.0 or 0%..100%
   * @param null|float|string $lightness
   *   0.0..1.0 or 0%..100%
   * @return array|null
   *   hue        => 0..360
   *   saturation => 0.0..1.0
   *   lightness  => 0.0..1.0
   */
  public function hsl($hue = NULL, $saturation = NULL, $lightness = NULL)
  {
    if ($hue === NULL) {
      // Get the HSL values:
      return self::rgb2hsl($this->red(), $this->green(), $this->blue());
    }

    // Set the HSL values:
    $rgb = self::hsl2rgb($hue, $saturation, $lightness);
    $this->rgb($rgb['red'], $rgb['green'], $rgb['blue']);

    return $this;
  }

  ///////////////////////////////////////////////////////////////////////////
  // Lightness-related methods.

  /**
   * Returns true for a dark color.
   *
   * @return  bool
   */
  public function isDark()
  {
    return $this->lightness() < 0.5;
  }

  /**
   * Returns true for a light color.
   *
   * @return  bool
   */
  public function isLight()
  {
    return $this->lightness() >= 0.5;
  }

  ///////////////////////////////////////////////////////////////////////////
  // Miscellaneous static methods.

  /**
   * Returns true if the string is a hex color string.
   * Leading '#' is optional.  Can be 6-digit or 3-digit.
   *
   * @return  bool
   * @param   string  $str
   */
  public static function isHexString($str)
  {
    $hex_digit = "[a-f0-9]";
    $pattern = "/^#?($hex_digit{6}|$hex_digit{3})$/i";
    return (bool) preg_match($pattern, $str);
  }

  /**
   * Mix two colors.
   * If called with only two parameters then the colors are mixed 50-50.
   *
   * @param int|string|Color $color1
   * @param int|string|Color $color2
   * @param float $frac1
   *   0.0..1.0 Fraction of $color1
   * @return Color
   */
  public static function mix($color1, $color2, $frac1 = 0.5)
  {
    $color1 = self::normalizeColor($color1);
    $color2 = self::normalizeColor($color2);

    $frac1 = (float) $frac1;
    if ($frac1 <= 0) {
      return $color2;
    }

    if ($frac1 >= 1) {
      return $color1;
    }

    // Get the red, green, blue and alpha parts of the new color:
    $frac2 = 1 - $frac1;
    $red   = round(($color1->red()   * $frac1) + ($color2->red()   * $frac2));
    $green = round(($color1->green() * $frac1) + ($color2->green() * $frac2));
    $blue  = round(($color1->blue()  * $frac1) + ($color2->blue()  * $frac2));
    $alpha = ($color1->alpha() * $frac1) + ($color2->alpha() * $frac2);

    // Create and return the mixed color:
    return new self($red, $green, $blue, $alpha);
  }

  /**
   * Blend two colors.
   *
   * This method is for setting one pixel ($color1) on top of another ($color2) on an image.
   * @see StarImage::setPixel
   *
   * For formulas:
   * @see http://www.w3.org/TR/2003/REC-SVG11-20030114/masking.html#SimpleAlphaBlending
   * @see http://en.wikipedia.org/wiki/Alpha_compositing#Alpha_blending
   *
   * @param int|string|Color $top_color
   * @param int|string|Color $bottom_color
   * @return Color
   */
  public static function blend($top_color, $bottom_color)
  {
    $top_color = self::normalizeColor($top_color);
    $bottom_color = self::normalizeColor($bottom_color);

    // Calculate resultant alpha:
    $a1 = $top_color->alpha();
    $a2 = $bottom_color->alpha();
    $a3 = $a1 + $a2 * (1 - $a1);

    // Calculate red, green and blue components of resultant color:
    $rgb1 = $top_color->rgb();
    $rgb2 = $bottom_color->rgb();
    $rgb3 = array();
    foreach ($rgb1 as $color => $value) {
      $rgb3[$color] = (($value * $a1) + ($rgb2[$color] * $a2 * (1 - $a1))) / $a3;
    }

    // Create and return new color:
    return new self($rgb3['red'], $rgb3['green'], $rgb3['blue'], $a3);
  }

  //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
  // Output methods:

  /**
   * Outputs the color as an RGB CSS string.
   *
   * @return string
   */
  public function rgbString()
  {
    $red   = $this->red();
    $green = $this->green();
    $blue  = $this->blue();
    return "rgb($red, $green, $blue)";
  }

  /**
   * Outputs the color as an RGBA CSS string.
   *
   * @return string
   */
  public function rgbaString()
  {
    $red   = $this->red();
    $green = $this->green();
    $blue  = $this->blue();
    $alpha = round($this->alpha(), 3);
    return "rgba($red, $green, $blue, $alpha)";
  }

  /**
   * Outputs the color as an HSL CSS string.
   *
   * @return string
   */
  public function hslString()
  {
    $hsl        = $this->hsl();
    $hue        = round($hsl['hue']);
    $saturation = round($hsl['saturation'] * 100) . '%';
    $lightness  = round($hsl['lightness']  * 100) . '%';
    return "hsl($hue, $saturation, $lightness)";
  }

  /**
   * Outputs the color as an RGBA CSS string.
   *
   * @return string
   */
  public function hslaString()
  {
    $hsla       = $this->hsla();
    $hue        = round($hsla['hue']);
    $saturation = round($hsla['saturation'] * 100) . '%';
    $lightness  = round($hsla['lightness']  * 100) . '%';
    $alpha      = round($hsla['alpha'], 3);
    return "hsla($hue, $saturation, $lightness, $alpha)";
  }

  /**
   * Outputs the color as a 6-digit hexadecimal string, or 8-digit if alpha is included.
   *
   * @param bool $include_alpha
   * @param bool $include_hash
   * @return string
   */
  public function hex($include_alpha = FALSE, $include_hash = TRUE)
  {
    if ($include_alpha) {
      $value = $this->value;
      $n_chars = 8;
    } else {
      $value = ($this->value >> 8) & 0xFFFFFF;
      $n_chars = 6;
    }
    return ($include_hash ? '#' : '') . str_pad(strtoupper(dechex($value)), $n_chars, '0', STR_PAD_LEFT);
  }

  /**
   * Default string representation of color is 8-digit RGBA hex string.
   * Not CSS compatible, but concise and informative.
   *
   * @return string
   */
  public function __toString()
  {
    return $this->hex(TRUE, TRUE);
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
   * @return array
   */
  public function toArray()
  {
    return array_merge($this->rgba(), $this->hsl(), array('hex' => $this->hex()));
  }

  /////////////////////////////////////////////////////////////////////////
  // Protected static methods to normalize parameters.

  /**
   * Convert a null, int, float or string to an int between 0..255.
   * Used for red, green, blue.
   *
   * @static
   * @param null|int|float|string $value
   *   0..255 or 0%..100%
   * @return int
   *   0..255
   */
  protected static function normalizeByte($value)
  {
    // Default to 0:
    if ($value === NULL) {
      return 0;
    }

    // Look for % value:
    if (is_string($value) && preg_match("/^((\d*\.)?\d+)%$/", trim($value), $matches)) {
      $value = $matches[1] / 100 * 255;
    }

    // Convert to int:
    $value = (int) round((float) $value);

    // Clamp value:
    if ($value < 0) {
      $value = 0;
    } elseif ($value > 255) {
      $value = 255;
    }

    return $value;
  }

  /**
   * Convert a value to a float between 0.0 and 1.0.
   * Used for saturation and lightness.
   *
   * @static
   * @param float|string $value
   *   0.0..1.0 or 0%..100%
   * @return float
   *   0.0..1.0
   */
  protected static function normalizeFraction($value)
  {
    // Default to 1.0:
    if ($value === NULL) {
      return 1.0;
    }

    // Check for percentage:
    if (is_string($value) && preg_match("/^((\d*\.)?\d+)%$/", trim($value), $matches)) {
      $value = $matches[1] / 100;
    }

    // Convert to float:
    $value = (float) $value;

    // Clamp value:
    if ($value < 0) {
      $value = 0;
    } elseif ($value > 1) {
      $value = 1;
    }

    return $value;
  }

  /**
   * Convert a fractional value to a byte value.
   * Used for alpha.
   *
   * @static
   * @param float|string $value
   *   0.0..1.0 or 0%..100%
   * @return int
   *   0..255
   */
  protected static function normalizeFractionByte($value)
  {
    return round(self::normalizeFraction($value) * 255);
  }

  /**
   * Converts value to a float in the range 0..360.
   * Used for hue.
   *
   * @param int|float $value
   * @return float
   *   0..360
   */
  protected static function normalizeDegree($value)
  {
    // Default to 0:
    if ($value === NULL) {
      return 0;
    }

    // Convert to float:
    $value = (float) $value;

    // Shift value to valid range:
    if ($value < 0) {
      // Add multiples of 360 so it's within range:
      return $value + (ceil(-$value / 360) * 360);
    }

    if ($value > 360) {
      // Subtract multiples of 360 so it's within range:
      return $value - (floor($value / 360) * 360);
    }

    return $value;
  }

  /**
   * Converts value to a Color, if not one already.
   * Use this to avoid creating a new object if the param is already a Color.
   *
   * @param int|string|Color $color
   */
  protected static function normalizeColor($color)
  {
    return ($color instanceof self) ? $color : new self($color);
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
   * @param int $red
   *   0..255
   * @param int $green
   *   0..255
   * @param int $blue
   *   0..255
   * @return array
   *   hue        => 0..360
   *   saturation => 0.0..1.0
   *   lightness  => 0.0..1.0
   */
  public static function rgb2hsl($red, $green, $blue)
  {
    // Convert the red, green and blue values to fractions:
    $red   = self::normalizeByte($red)   / 255;
    $green = self::normalizeByte($green) / 255;
    $blue  = self::normalizeByte($blue)  / 255;

    // Get the min and max values:
    $min = min($red, $green, $blue);
    $max = max($red, $green, $blue);

    // Calculate lightness:
    $lightness = ($min + $max) / 2;
    if ($min == $max) {
      // Grey:
      $saturation = 0;
      $hue = 0; // Actually undefined (without hue), but 0 is standard.
    } else {
      // Calculate saturation:
      $d = $max - $min;
      if ($lightness < 0.5) {
        $saturation = $d / ($max + $min);
      } else {
        $saturation = $d / (2 - $max - $min);
      }

      // Calculate hue:
      if ($red == $max) {
        $hue = ($green - $blue) / $d;
      } elseif ($green == $max) {
        $hue = 2 + (($blue - $red) / $d);
      } else {
        $hue = 4 + (($red - $green) / $d);
      }

      // Convert hue to degrees:
      $hue *= 60;
      if ($hue < 0) {
        $hue += 360;
      }
    }

    return array(
      'hue'        => $hue,
      'saturation' => $saturation,
      'lightness'  => $lightness
    );
  }

  /**
   * Convert HSL values to RGB.
   *
   * @param float $hue
   *   0..360
   * @param float $saturation
   *   0.0..1.0 or 0%..100%
   * @param float $lightness
   *   0.0..1.0 or 0%..100%
   * @return array
   *   red   => 0..255
   *   green => 0..255
   *   blue  => 0..255
   */
  public static function hsl2rgb($hue, $saturation, $lightness)
  {
    // Convert values to fractions:
    $hue        = self::normalizeDegree($hue) / 360;
    $saturation = self::normalizeFraction($saturation);
    $lightness  = self::normalizeFraction($lightness);

    if ($saturation == 0) {
      // Grey:
      $red = $green = $blue = round($lightness * 255);
    } else {
      $m2 = ($lightness <= 0.5) ? ($lightness * ($saturation + 1)) : ($lightness + $saturation - ($lightness * $saturation));
      $m1 = $lightness * 2 - $m2;
      $red   = self::hue2rgb($m1, $m2, $hue + (1 / 3));
      $green = self::hue2rgb($m1, $m2, $hue);
      $blue  = self::hue2rgb($m1, $m2, $hue - (1 / 3));
    }

    return array(
      'red'   => $red,
      'green' => $green,
      'blue'  => $blue
    );
  }

  /**
   * Protected helper function for converting HSL values to RGB.
   * Return value is color component 0..255.
   *
   * @param float $m1
   * @param float $m2
   * @param float $h
   * @return int
   *   0..255
   */
  protected static function hue2rgb($m1, $m2, $h)
  {
    if ($h < 0) {
      $h += 1;
    } elseif ($h > 1) {
      $h -= 1;
    }
    if ($h * 6 < 1) {
      $c = $m1 + (($m2 - $m1) * $h * 6);
    } elseif ($h * 2 < 1) {
      $c = $m2;
    } elseif ($h * 3 < 2) {
      $c = $m1 + (($m2 - $m1) * (2 / 3 - $h) * 6);
    } else {
      $c = $m1;
    }
    return round($c * 255);
  }

  /**
   * Convert an RGB color value to a 2-digit hex string.
   *
   * @static
   * @param int|string $byte
   *   0..255 or 0%..100%
   * @return string
   */
  public static function byte2hex($byte)
  {
    $byte = self::normalizeByte($byte);
    return str_pad(strtoupper(dechex($byte)), 2, '0', STR_PAD_LEFT);
  }

  /**
   * Convert RGB values to a 6-digit hex string.
   *
   * @param int|string $red
   *   0..255 or 0%..100%
   * @param int|string $green
   *   0..255 or 0%..100%
   * @param int|string $blue
   *   0..255 or 0%..100%
   * @return string
   */
  public static function rgb2hex($red, $green, $blue)
  {
    return self::byte2hex($red) . self::byte2hex($green) . self::byte2hex($blue);
  }

  /**
   * Convert 3 or 6-digit hex string to RGB values.
   *
   * @param string
   * @return array
   */
  public static function hex2rgb($hex)
  {
    if (!self::isHexString($hex)) {
      return FALSE;
    }

    // Remove any leading # character:
    if ($hex[0] == '#') {
      $hex = substr($hex, 1);
    }

    if (strlen($hex) == 6) {
      // 6-digit hex string. Split string into bytes:
      $red   = hexdec(substr($hex, 0, 2));
      $green = hexdec(substr($hex, 2, 2));
      $blue  = hexdec(substr($hex, 4, 2));
    } else {
      // 3-digit hex string. Double each hex digit:
      $red   = hexdec($hex[0] . $hex[0]);
      $green = hexdec($hex[1] . $hex[1]);
      $blue  = hexdec($hex[2] . $hex[2]);
    }

    return array(
      'red'   => $red,
      'green' => $green,
      'blue'  => $blue,
    );
  }

  /////////////////////////////////////////////////////////////////////////
  // Named colors

  /**
   * Array for mapping color names to hex values.
   *
   * @var array
   */
  protected static $colorNames = array(
    'aliceblue'            => 'F0F8FF',
    'amethyst'             => '9966CC',
    'antiquewhite'         => 'FAEBD7',
    'aqua'                 => '00FFFF',
    'aquamarine'           => '7FFFD4',
    'azure'                => 'F0FFFF',
    'beige'                => 'F5F5DC',
    'bisque'               => 'FFE4C4',
    'black'                => '000000',
    'blanchedalmond'       => 'FFEBCD',
    'blue'                 => '0000FF',
    'blueviolet'           => '8A2BE2',
    'brown'                => 'A52A2A',
    'burlywood'            => 'DEB887',
    'cadetblue'            => '5F9EA0',
    'chartreuse'           => '7FFF00',
    'chocolate'            => 'D2691E',
    'coral'                => 'FF7F50',
    'cornflowerblue'       => '6495ED',
    'cornsilk'             => 'FFF8DC',
    'crimson'              => 'DC143C',
    'cyan'                 => '00FFFF',
    'darkblue'             => '00008B',
    'darkcyan'             => '008B8B',
    'darkgoldenrod'        => 'B8860B',
    'darkgray'             => 'A9A9A9',
    'darkgreen'            => '006400',
    'darkgrey'             => 'A9A9A9',
    'darkkhaki'            => 'BDB76B',
    'darkmagenta'          => '8B008B',
    'darkolivegreen'       => '556B2F',
    'darkorange'           => 'FF8C00',
    'darkorchid'           => '9932CC',
    'darkred'              => '8B0000',
    'darksalmon'           => 'E9967A',
    'darkseagreen'         => '8FBC8F',
    'darkslateblue'        => '483D8B',
    'darkslategray'        => '2F4F4F',
    'darkslategrey'        => '2F4F4F',
    'darkturquoise'        => '00CED1',
    'darkviolet'           => '9400D3',
    'deeppink'             => 'FF1493',
    'deepskyblue'          => '00BFFF',
    'dimgray'              => '696969',
    'dimgrey'              => '696969',
    'dodgerblue'           => '1E90FF',
    'firebrick'            => 'B22222',
    'floralwhite'          => 'FFFAF0',
    'forestgreen'          => '228B22',
    'fuchsia'              => 'FF00FF',
    'gainsboro'            => 'DCDCDC',
    'ghostwhite'           => 'F8F8FF',
    'gold'                 => 'FFD700',
    'goldenrod'            => 'DAA520',
    'gray'                 => '808080',
    'green'                => '008000',
    'greenyellow'          => 'ADFF2F',
    'grey'                 => '808080',
    'honeydew'             => 'F0FFF0',
    'hotpink'              => 'FF69B4',
    'indianred'            => 'CD5C5C',
    'indigo'               => '4B0082',
    'ivory'                => 'FFFFF0',
    'khaki'                => 'F0E68C',
    'lavender'             => 'E6E6FA',
    'lavenderblush'        => 'FFF0F5',
    'lawngreen'            => '7CFC00',
    'lemonchiffon'         => 'FFFACD',
    'lightblue'            => 'ADD8E6',
    'lightcoral'           => 'F08080',
    'lightcyan'            => 'E0FFFF',
    'lightgoldenrodyellow' => 'FAFAD2',
    'lightgray'            => 'D3D3D3',
    'lightgreen'           => '90EE90',
    'lightgrey'            => 'D3D3D3',
    'lightpink'            => 'FFB6C1',
    'lightsalmon'          => 'FFA07A',
    'lightseagreen'        => '20B2AA',
    'lightskyblue'         => '87CEFA',
    'lightslategray'       => '778899',
    'lightslategrey'       => '778899',
    'lightsteelblue'       => 'B0C4DE',
    'lightyellow'          => 'FFFFE0',
    'lime'                 => '00FF00',
    'limegreen'            => '32CD32',
    'linen'                => 'FAF0E6',
    'magenta'              => 'FF00FF',
    'maroon'               => '800000',
    'mediumaquamarine'     => '66CDAA',
    'mediumblue'           => '0000CD',
    'mediumorchid'         => 'BA55D3',
    'mediumpurple'         => '9370DB',
    'mediumseagreen'       => '3CB371',
    'mediumslateblue'      => '7B68EE',
    'mediumspringgreen'    => '00FA9A',
    'mediumturquoise'      => '48D1CC',
    'mediumvioletred'      => 'C71585',
    'midnightblue'         => '191970',
    'mintcream'            => 'F5FFFA',
    'mistyrose'            => 'FFE4E1',
    'moccasin'             => 'FFE4B5',
    'navajowhite'          => 'FFDEAD',
    'navy'                 => '000080',
    'oldlace'              => 'FDF5E6',
    'olive'                => '808000',
    'olivedrab'            => '6B8E23',
    'orange'               => 'FFA500',
    'orangered'            => 'FF4500',
    'orchid'               => 'DA70D6',
    'palegoldenrod'        => 'EEE8AA',
    'palegreen'            => '98FB98',
    'paleturquoise'        => 'AFEEEE',
    'palevioletred'        => 'DB7093',
    'papayawhip'           => 'FFEFD5',
    'peachpuff'            => 'FFDAB9',
    'peru'                 => 'CD853F',
    'pink'                 => 'FFC0CB',
    'plum'                 => 'DDA0DD',
    'powderblue'           => 'B0E0E6',
    'purple'               => '800080',
    'red'                  => 'FF0000',
    'rosybrown'            => 'BC8F8F',
    'royalblue'            => '4169E1',
    'saddlebrown'          => '8B4513',
    'salmon'               => 'FA8072',
    'sandybrown'           => 'F4A460',
    'seagreen'             => '2E8B57',
    'seashell'             => 'FFF5EE',
    'sienna'               => 'A0522D',
    'silver'               => 'C0C0C0',
    'skyblue'              => '87CEEB',
    'slateblue'            => '6A5ACD',
    'slategray'            => '708090',
    'slategrey'            => '708090',
    'snow'                 => 'FFFAFA',
    'springgreen'          => '00FF7F',
    'steelblue'            => '4682B4',
    'tan'                  => 'D2B48C',
    'teal'                 => '008080',
    'thistle'              => 'D8BFD8',
    'tomato'               => 'FF6347',
    'turquoise'            => '40E0D0',
    'violet'               => 'EE82EE',
    'wheat'                => 'F5DEB3',
    'white'                => 'FFFFFF',
    'whitesmoke'           => 'F5F5F5',
    'yellow'               => 'FFFF00',
    'yellowgreen'          => '9ACD32',
  );

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
   * Check if a given string is a color name.
   *
   * @static
   * @param string $name
   * @return bool
   */
  public static function isColorName($name)
  {
    $name = strtolower($name);
    return isset(self::$colorNames[$name]);
  }

  /**
   * Convert a color name to a 6-digit hex value.
   *
   * @static
   * @param $name
   * @return bool
   */
  private static function colorName2hex($name)
  {
    $name = strtolower($name);
    return isset(self::$colorNames[$name]) ? self::$colorNames[$name] : FALSE;
  }
}
