<?php

declare(strict_types = 1);

namespace Superclasses\Collections;

use InvalidArgumentException;

class Type
{
    // region Traits

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

    // endregion

    // region Keys

    /**
     * Convert any PHP value into a unique string key.
     *
     * @param mixed $key The value to convert.
     * @return string The unique string key.
     */
    public static function getStringKey(mixed $key): string
    {
        $type = get_debug_type($key);

        // Core types.
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

        // Resources.
        if (str_starts_with($type, 'resource')) {
            return 'r:' . get_resource_id($key);
        }

        // Objects.
        if (is_object($key)) {
            return 'o:' . spl_object_id($key);
        }

        // Not sure if this can ever actually happen. gettype() can return 'unknown type' but
        // get_debug_type() has no equivalent.
        throw new InvalidArgumentException("Key has unknown type.");
    }

    // endregion
}
