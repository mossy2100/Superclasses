<?php

declare(strict_types=1);

namespace Superclasses;

use InvalidArgumentException;

require_once __DIR__ . '/Set.php';

/**
 * This class provides JSON-like encoding with a few differences:
 * 1. Integer keys for PHP arrays (non-lists) are not quoted as they would be in JSON objects.
 * 2. PHP arrays are encoded with square brackets.
 * 3. Objects are encoded using a HTML tag-like syntax (angle brackets), with UML symbols.
 * 4. Floats are encoded with a decimal point, 'E', or both, to differentiate them from ints.
 *
 * The purpose of the class is to offer a more readable alternative to var_dump(), print_r(), or
 * serialize().
 */
class Stringify
{
    /**
     * Convert a value to a readable string representation.
     *
     * @param mixed $value The value to encode.
     * @param int $indent_level The level of indentation for this structure (default 0).
     * @return string The string representation of the value.
     */
    public static function encode(mixed $value, int $indent_level = 0): string
    {
        // Get the type.
        $type = get_debug_type($value);

        // Resources aren't supported.
        if (str_starts_with($type, 'resource')) {
            throw new InvalidArgumentException("Resources cannot be encoded.");
        }

        // Call the relevant encode method.
        return match ($type) {
            'null', 'bool', 'int', 'string' => json_encode($value),
            'float' => self::encodeFloat($value),
            'array' => self::encodeArray($value, $indent_level),
            default => self::encodeObject($value, $type, $indent_level)
        };
    }

    /**
     * Encode a float in such a way that it doesn't look like an integer.
     *
     * @param float $value The float value to encode.
     * @return string The string representation of the float.
     */
    public static function encodeFloat(float $value): string
    {
        // Convert the float to a string using the default method.
        $s = (string)$value;

        // Make sure the string has a decimal point or E.
        if (!str_contains($s, '.') && !str_contains($s, 'E')) {
            $s .= '.0';
        }

        return $s;
    }

    /**
     * Encode a PHP array as as list with square brackets or an associative array with braces and
     * key-value pairs.
     *
     * @param array $ary The array to encode.
     * @param int $indent_level The level of indentation for this structure (default 0).
     * @return string The string representation of the array.
     */
    public static function encodeArray(array $ary, int $indent_level = 0): string
    {
        // Encode a list like a JSON array. All elements will be on one line with no indentation.
        if (array_is_list($ary)) {
            return '[' . implode(', ', array_map('Superclasses\Stringify::encode', $ary)) . ']';
        }

        // Encode an associative array like a JSON object.
        // Each key-value pair will be on its own line.
        $pairs = [];
        $indent = str_repeat(' ', 4 * ($indent_level + 1));

        foreach ($ary as $key => $value) {
            $pairs[] = $indent . self::encode($key) . ': ' .
                self::encode($value, $indent_level + 1);
        }

        return "{\n" . implode(",\n", $pairs) . "\n" . str_repeat(' ', 4 * $indent_level) . '}';
    }

    /**
     * Encode an object like an HTML tag with bonus UML symbols to indicate visibility.
     *
     * @param object $obj The object to encode.
     * @param int $indent_level The level of indentation for this structure (default 0).
     * @return string The string representation of the object.
     */
    public static function encodeObject(object $obj, string $class, int $indent_level = 0): string
    {
        // Convert the object to an array to get its properties.
        // This works better than reflection, as new properties can be created when converting the
        // object to an array (example: DateTime).
        $a = (array)$obj;

        // Early return if no properties.
        if (count($a) === 0) {
            return "<$class>";
        }

        // Generate the strings for key-value pairs. Each will be on its own line.
        $pairs = [];
        $indent = str_repeat(' ', 4 * ($indent_level + 1));

        foreach ($a as $key => $value) {
            // Split on null bytes to determine the property name and visibility.
            $name_parts = explode("\0", $key);

            switch (count($name_parts)) {
                case 1:
                    $vis_symbol = '+';
                    break;

                case 3:
                    $vis_symbol = $name_parts[1] === '*' ? '#' : '-';
                    $key = $name_parts[2];
                    break;

                default:
                    // If there are 4 parts, the object is an anonymous class with a property
                    // indicating where the class is defined. We don't care about that, so ignore it.
                    continue 2;
            }

            $pairs[] = $indent . $vis_symbol . $key . ': ' .
                self::encode($value, $indent_level + 1);
        }

        return "<$class\n" . implode(",\n", $pairs) . '>';
    }
}
