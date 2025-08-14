<?php

declare(strict_types = 1);

namespace Superclasses;

use InvalidArgumentException;

class Type
{
    /**
     * Check if a value is a number, i.e. an integer or a float.
     * This varies from is_numeric(), which also returns true for numeric strings.
     */
    public static function isNumber(mixed $value)
    {
        return is_int($value) || is_float($value);
    }

    /**
     * Check if a value is an unsigned integer.
     *
     * @param mixed $value The value to check.
     * @return bool True if the value is an unsigned integer, false otherwise.
     */
    public static function isUnsignedInt(mixed $value): bool
    {
        return is_int($value) && $value >= 0;
    }

    /**
     * Get the sign of a number.
     *
     * @param float $value The number.
     * @return int -1 if negative or -0.0, 1 if positive or 0.0.
     */
    public static function sign(float $value): int
    {
        // Guard. This method is only valid for numbers.
        if (is_nan($value)) {
            throw new InvalidArgumentException("NaN is not a valid number.");
        }

        if ($value == 0) {
            // Distinguish +0.0 and -0.0 without warnings.
            return fdiv(1.0, $value) === -INF ? -1 : 1;
        }

        return $value < 0 ? -1 : 1;
    }

    /**
     * Copy the sign of one number to another.
     *
     * @param float $num The number to copy the sign to.
     * @param float $sign_source The number to copy the sign from.
     * @return float The number with the sign of $sign_source.
     */
    public static function copySign(float $num, float $sign_source): float
    {
        // Guard. This method is only valid for numbers.
        if (is_nan($num) || is_nan($sign_source)) {
            throw new InvalidArgumentException("Both parameters must be numbers.");
        }

        return abs($num) * Type::sign($sign_source);
    }

    /**
     * Get the class name of an object.
     *
     * If it's an anonymous class, the class name will be truncated to the first null byte. This will return "class"
     * for anonymous classes, or a parent class or interface name if known.
     *
     * @param object $value The object.
     * @return string The class name.
     */
    public static function getClassName(object $value): string
    {
        $class  = get_class($value);
        $nulpos = strpos($class, "\0");
        if ($nulpos !== false) {
            $class = substr($class, 0, $nulpos);
        }
        return $class;
    }

    /**
     * Check if object or class uses a given trait.
     * Handle both class names and objects, including trait inheritance.
     */
    public static function usesTrait(object|string $obj_or_class, string $trait): bool
    {
        $all_traits = self::getTraitsRecursive($obj_or_class);
        return in_array($trait, $all_traits);
    }

    /**
     * Get all traits used by an object or class, including parent classes and trait inheritance.
     */
    private static function getTraitsRecursive(object|string $obj_or_class): array
    {
        // Get class name.
        $class = is_object($obj_or_class) ? get_class($obj_or_class) : $obj_or_class;

        // Collection for traits.
        $traits = [];

        // Get traits from current class and all parent classes.
        do {
            $class_traits = class_uses($class);
            $traits       = array_merge($traits, $class_traits);

            // Also get traits used by the traits themselves.
            foreach ($class_traits as $trait) {
                $trait_traits = self::getTraitsRecursive($trait);
                $traits       = array_merge($traits, $trait_traits);
            }
        } while ($class = get_parent_class($class));

        return array_unique($traits);
    }


    /**
     * Convert any PHP value into a unique string key.
     *
     * @param mixed $key The value to convert.
     * @return string The unique string key.
     */
    public static function getStringKey(mixed $key): string
    {
        $type = get_debug_type($key);
        switch ($type) {
            case 'null':
                return 'n';

            case 'bool':
                return 'b:' . ($key ? 'T' : 'F');

            case 'int':
                return 'i:' . $key;

            case 'float':
                return 'f:' . $key;

            case 'string':
                return 's:' . strlen($key) . ':' . $key;

            case 'array':
                $array_item_keys = array_map('Dictionary::getStringKey', $key);
                return 'a:' . count($key) . ':[' . implode(', ', $array_item_keys) . ']';
        }

        if (is_object($key)) {
            return 'o:' . spl_object_id($key);
        }

        if (str_starts_with($type, 'resource')) {
            return 'r:' . get_resource_id($key);
        }

        // Not sure if this can ever actually happen. gettype() can return 'unknown type' but
        // get_debug_type() has no equivalent.
        throw new InvalidArgumentException("Key has unknown type.");
    }
}
