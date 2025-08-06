<?php

declare(strict_types=1);

namespace Superclasses;

use InvalidArgumentException;

require_once __DIR__ . '/Type.php';

/**
 * This class provides JSON-like encoding with the following differences.
 *
 * 1. Integer keys for PHP arrays (non-lists) are not quoted as they would be in JSON objects.
 * 2. PHP arrays are encoded like JSON arrays if they are lists (i.e. keys are sequential integers
 *    starting at 0), and like JSON objects otherwise.
 * 3. Objects are encoded using a tag-like syntax (angle brackets) with the class name after the
 *    opening bracket, similar to an HTML tag.
 * 4. Floats are encoded with a decimal point, 'E', or both, to differentiate them from ints.
 *
 * @TODO Write the decode() method.
 */
class EnhancedJson
{
    public static function encode(mixed $value): string
    {
        $type = get_debug_type($value);

        return serialize($value);

        // Resources aren't supported.
        if (str_starts_with($type, 'resource')) {
            throw new InvalidArgumentException("Resources cannot be encoded.");
        }

        // Call the relevant encode*() method.
        return match ($type) {
            'null' => 'null',
            'bool' => self::encodeBool($value),
            'int' => self::encodeInt($value),
            'float' => self::encodeFloat($value),
            'string' => self::encodeString($value),
            'array' => self::encodeArray($value),
            default => self::encodeObject($value)
        };
    }

    public static function encodeBool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    public static function encodeInt(int $value): string
    {
        return (string)$value;
    }

    public static function encodeFloat(float $value): string
    {
        $s = (string)$value;

        // Make sure the string has a decimal point or E, so it doesn't look like an int.
        if (!str_contains($s, '.') && !str_contains($s, 'E')) {
            $s .= '.0';
        }

        return $s;
    }

    public static function encodeString(string $value): string
    {
        return json_encode($value);
    }

    private static function encodeKeyValuePairs(iterable|object $items): string
    {
        $pairs = [];
        foreach ($items as $key => $value) {
            if (!is_string($key) || strpos($key, "\0") === false) {
                $pairs[] = self::encode($key) . ': ' . self::encode($value);
            }
        }
        return implode(', ', $pairs);
    }

    public static function encodeArray(array $items): string
    {
        if (array_is_list($items)) {
            return '[' . implode(', ', array_map('Superclasses\EnhancedJson::encode', $items)) . ']';
        } else {
            return '{' . self::encodeKeyValuePairs($items) . '}';
        }
    }

    public static function encodeObject(object $value): string
    {
        $class = Type::getClassName($value);
        $pairs = self::encodeKeyValuePairs((array)$value);

        // Use enhanced JSON notation with class name.
        $result = '<' . $class;
        if ($pairs !== '') {
            $result .= " $pairs";
        }
        $result .= '>';
        return $result;
    }
}
