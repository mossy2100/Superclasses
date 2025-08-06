<?php

declare(strict_types=1);

namespace Superclasses;

use Stringable;
use Countable;
use IteratorAggregate;
use Traversable;
use ArrayIterator;
use InvalidArgumentException;

require_once __DIR__ . '/Type.php';

class TypeSet implements Stringable, Countable, IteratorAggregate
{
    public private(set) array $types = [];

    public function __construct(string|iterable $types = '')
    {
        // Convert union type syntax (e.g. 'string|int') into array of types.
        if (is_string($types)) {
            $types = explode('|', $types);
        }

        // Collect types into array.
        foreach ($types as $type) {
            // Check type type.
            if (!is_string($type)) {
                throw new InvalidArgumentException("Allowed types must be specified as a string or an iterable collection of strings.");
            }

            // Trim just in case they did something like 'string | int'.
            $type = trim($type);

            // Support the question mark nullable notation (e.g. '?string').
            if ($type !== '' && $type[0] === '?') {
                $nullable_type = substr($type, 1);
                if ($nullable_type === '') {
                    throw new InvalidArgumentException("Invalid nullable type syntax.");
                }
                $this->add('null');
                $this->add($nullable_type);
            } else {
                $this->add($type);
            }
        }
    }

    /**
     * Add a type to the set, if not already present.
     */
    public function add(string $type)
    {
        if ($type !== '' && !in_array($type, $this->types)) {
            $this->types[] = $type;
        }
    }

    /**
     * Helper function to get a type name for a given value.
     * A more specific type name is preferred when possible.
     */
    public static function getType(mixed $value)
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
     * Get the type name from a value and add it to the set.
     */
    public function addValueType(mixed $value)
    {
        return $this->add(self::getType($value));
    }

    /**
     * Remove a type from the set, if present.
     */
    public function remove(string $type)
    {
        $this->types = array_values(array_diff($this->types, [$type]));
    }

    /**
     * Check if a value matches one of the types in the TypeSet.
     */
    public function match(mixed $value): bool
    {
        // If the types include 'mixed' then any type is allowed.
        if (in_array('mixed', $this->types)) {
            return true;
        }

        // Check for simple type or class match.
        if (in_array(get_debug_type($value), $this->types)) {
            return true;
        }

        // Check scalar.
        if (is_scalar($value) && in_array('scalar', $this->types)) {
            return true;
        }

        // Check number.
        if (Type::isNumber($value) && in_array('number', $this->types)) {
            return true;
        }

        // Additional checks for objects.
        if (is_object($value)) {
            // Any object type.
            if (in_array('object', $this->types)) {
                return true;
            }

            // Check value against classes, interfaces, and traits.
            foreach ($this->types as $type) {
                // Check classes and interfaces.
                if ((class_exists($type) || interface_exists($type)) && $value instanceof $type) {
                    return true;
                }

                // Check traits.
                if (trait_exists($type) && Type::usesTrait($value, $type)) {
                    return true;
                }
            }
        }

        // Check for any resource or specific resource type.
        if (is_resource($value) && (in_array('resource', $this->types) ||
            in_array(get_resource_type($value), $this->types))) {
            return true;
        }

        // Check iterable.
        if (is_iterable($value) && in_array('iterable', $this->types)) {
            return true;
        }

        // Check callable.
        if (is_callable($value) && in_array('callable', $this->types)) {
            return true;
        }

        return false;
    }

    public function contains(string $type)
    {
        return in_array($type, $this->types);
    }

    public function isNullOnly(): bool
    {
        return $this->types === ['null'];
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Stringable implementation

    public function __toString(): string
    {
        return implode('|', $this->types);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Countable implementation

    public function count(): int
    {
        return count($this->types);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // IteratorAggregate implementation

    /**
     * Get iterator for foreach loops.
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->types);
    }
}
