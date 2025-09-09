<?php

declare(strict_types = 1);

namespace Superclasses\Types;

use InvalidArgumentException;

class Type
{
    /**
     * Helper function to get a type name for a given value.
     * A more specific type name (for example, a class name or resource type) is preferred when possible.
     *
     * @param mixed $value The value to get the type name for.
     * @return string The type name for the value.
     */
    public static function getType(mixed $value): string
    {
        $type = get_debug_type($value);

        // Resources.
        if (str_starts_with($type, 'resource')) {
            // Get the resource name if possible, otherwise generic 'resource'.
            return is_resource($value) ? get_resource_type($value) : 'resource';
        }

        // Anonymous classes.
        if (str_contains($type, '@anonymous')) {
            // Return the base class or interface name if there is one, otherwise generic 'object'.
            $class = substr($type, 0, -10);
            return $class === 'class' ? 'object' : $class;
        }

        // Return null, bool, int, float, string, or array.
        return $type;
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
     * Check if an object or class uses a given trait.
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
                // @todo Check for circular references here to prevent infinite recursion.
                $array_item_keys = array_map('Type::getStringKey', $key);
                return 'a:' . count($key) . ':[' . implode(', ', $array_item_keys) . ']';
        }

        if (str_starts_with($type, 'resource')) {
            return 'r:' . get_resource_id($key);
        }

        if (is_object($key)) {
            return 'o:' . spl_object_id($key);
        }

        // Not sure if this can ever actually happen. gettype() can return 'unknown type' but
        // get_debug_type() has no equivalent.
        throw new InvalidArgumentException("Key has unknown type.");
    }
}
