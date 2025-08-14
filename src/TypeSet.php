<?php

declare(strict_types=1);

namespace Superclasses;

use InvalidArgumentException;

class TypeSet extends Set
{
    /**
     * Constructor.
     *
     * @param string|iterable $types The type names to add to the set.
     * @throws InvalidArgumentException If any type is invalid.
     */
    public function __construct(string|iterable $types = '')
    {
        parent::__construct($types);

        // Convert union type syntax (e.g. 'string|int') into an array of type names.
        if (is_string($types)) {
            $types = explode('|', $types);
        }

        // Add types to the set.
        foreach ($types as $type) {
            // Check the type.
            if (!is_string($type)) {
                throw new InvalidArgumentException("Types must be provided as strings.");
            }

            // Trim just in case they did something like 'string | int'.
            $type = trim($type);

            // Support the question mark nullable notation (e.g. '?string').
            if (strlen($type) >= 2 && $type[0] === '?') {
                // Add null and the type being made nullable.
                $this->add('null', substr($type, 1));
            } else {
                $this->add($type);
            }
        }
    }

    /**
     * Checks if the provided string looks like it could be a valid type. That includes core types and pseudotypes,
     * resource types, and classes.
     *
     * For examples of resource names:
     * @see https://www.php.net/manual/en/resource.php
     *
     * For valid class names:
     * @see https://www.php.net/manual/en/language.oop5.basic.php
     *
     * @param mixed $item The item to check.
     * @return bool True if the item is a valid type, false otherwise.
     */
    #[\Override]
    public function isItemAllowed(mixed $item): bool
    {
        // Check the item is a string.
        if (!is_string($item)) {
            return false;
        }

        $type = trim($item);

        // Ignore blanks.
        if ($type === '') {
            return false;
        }

        // Check for simple types, pseudotypes, and resource types.
        if (preg_match("/^[a-zA-Z0-9._ ]+$/", $type)) {
            return true;
        }

        // Check for class names.
        $class = "[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*";
        $rx_class = "/^\\\\?(?:{$class})(?:\\\\{$class})*$/";
        return (bool)preg_match($rx_class, $type);
    }

    /**
     * Add one item to the set.
     *
     * @param mixed $item The item to add to the set.
     * @return $this The modified set.
     */
    #[\Override]
    public function addOne(mixed $item): static
    {
        // Call the parent method.
        parent::addOne($item);

        // Reduce the set as much as possible.
        $this->simplify();

        // Return $this for chaining.
        return $this;
    }

    /**
     * Helper function to get a type name for a given value.
     * A more specific type name is preferred when possible.
     *
     * @param mixed $value The value to get the type name for.
     * @return string The type name for the value.
     */
    public static function getValueType(mixed $value): string
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
     *
     * @param mixed $value The value to get the type name from.
     * @return void
     */
    public function addValueType(mixed $value): void
    {
        $this->add(self::getValueType($value));
    }

    /**
     * Check if a value matches one of the types in the TypeSet.
     *
     * @param mixed $value The value to check.
     * @return bool True if the value's type matches one of the types in the TypeSet, false otherwise.
     */
    public function match(mixed $value): bool
    {
        // If the types include 'mixed', any type is allowed.
        if ($this->contains('mixed')) {
            return true;
        }

        // Check for a simple type or class name match.
        if ($this->contains(get_debug_type($value))) {
            return true;
        }

        // Check scalar.
        if (is_scalar($value) && $this->contains('scalar')) {
            return true;
        }

        // Check number.
        if (Type::isNumber($value) && $this->contains('number')) {
            return true;
        }

        // Check uint.
        if (Type::isUnsignedInt($value) && $this->contains('uint')) {
            return true;
        }

        // Additional checks for objects.
        if (is_object($value)) {
            // Any object type.
            if ($this->contains('object')) {
                return true;
            }

            // Check value against classes, interfaces, and traits.
            foreach ($this as $type) {
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
        if (is_resource($value) && (
                $this->contains('resource') ||
                $this->contains(get_resource_type($value))
            )) {
            return true;
        }

        // Check iterable.
        if (is_iterable($value) && $this->contains('iterable')) {
            return true;
        }

        // Check callable.
        if (is_callable($value) && $this->contains('callable')) {
            return true;
        }

        return false;
    }

    /**
     * Check if the set contains only null.
     *
     * @return bool True if the set contains only null, false otherwise.
     */
    public function isNullOnly(): bool
    {
        return $this->count() === 1 && $this->contains('null');
    }

    /**
     * Reduce the set of types as much as possible.
     *
     * @return void
     */
    private function simplify(): void
    {
        // If no types were specified, or the set contains mixed, reduce to mixed only (i.e. any type is allowed).
        if ($this->count() === 0 || $this->contains('mixed')) {
            $this->clear()->add('mixed');
            return;
        }

        // Expand group types to simplify the reduction.
        if ($this->contains('scalar')) {
            $this->add('int', 'float', 'bool', 'string');
        }
        if ($this->contains('number')) {
            $this->add('int', 'float');
        }

        // Reduce to scalar if possible.
        if ($this->containsAll(['int', 'float', 'bool', 'string'])) {
            $this->remove('int', 'uint', 'float', 'number', 'bool', 'string');
            $this->add('scalar');
        }

        // Reduce to number if possible.
        if ($this->containsAll(['int', 'float'])) {
            $this->remove('int', 'float');
            $this->add('number');
        }

        // Remove uint if possible.
        if ($this->contains('uint') && $this->containsAny(['int', 'number', 'scalar'])) {
            $this->remove('uint');
        }

        // If 'object' is in the set, eliminate any names that must be class names.
        if ($this->contains('object')) {
            foreach ($this->items as $item) {
                // Check for a backslash. If there is one, it must be a class name.
                if (str_contains($item, '\\')) {
                    $this->remove($item);
                }
            }
        }

        // If 'resource' is in the set, eliminate any names that must be resource names.
        if ($this->contains('resource')) {
            foreach ($this->items as $item) {
                // Check for a dot or space. If there is one, it must be a resource name.
                if (preg_match('/[. ]/', $item)) {
                    $this->remove($item);
                }
            }
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Stringable implementation

    public function __toString(): string
    {
        return implode('|', $this->items);
    }
}
