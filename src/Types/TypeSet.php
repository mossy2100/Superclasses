<?php

declare(strict_types = 1);

namespace Superclasses\Types;

use Countable;
use InvalidArgumentException;
use Superclasses\Math\Numbers;

class TypeSet implements Countable
{
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Properties

    /**
     * Items in the set.
     *
     * @var array<string>
     */
    private(set) array $types = [];

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Constructor

    /**
     * Constructor.
     *
     * @param string|iterable $types The type names to add to the set.
     * @throws InvalidArgumentException If any type is invalid.
     */
    public function __construct(string|iterable $types = '')
    {
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
            if (strlen($type) > 1 && $type[0] === '?') {
                // Add null and the type being made nullable.
                $this->add('null', substr($type, 1));
            }
            else {
                // This will throw if the type is blank or '?' or otherwise invalid.
                $this->add($type);
            }
        }
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Helper methods

    /**
     * Convert a string or iterable of types into a TypeSet, if necessary.
     *
     * @param string|iterable $types The types to convert.
     * @return self The converted TypeSet.
     */
    public static function convertToTypeSet(string|iterable $types = ''): self
    {
        return $types instanceof self ? $types : new self($types);
    }

    /**
     * Checks if the provided string looks like a valid type. That includes core types, pseudotypes, resource types,
     * and classes.
     *
     * For examples of resource names:
     * @see https://www.php.net/manual/en/resource.php
     *
     * For valid class names:
     * @see https://www.php.net/manual/en/language.oop5.basic.php
     *
     * @param mixed $type The type to check.
     * @return bool True if the type is a valid type, false otherwise.
     */
    public function isValid(mixed $type): bool
    {
        // Check the type is a string.
        if (!is_string($type)) {
            return false;
        }

        $type = trim($type);

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
        $rx_class = "/^\\\\?({$class})(?:\\\\{$class})*$/";
        return (bool)preg_match($rx_class, $type);
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
        if ($this->contains('scalar') && is_scalar($value)) {
            return true;
        }

        // Check number.
        if ($this->contains('number') && Numbers::isNumber($value)) {
            return true;
        }

        // Check uint.
        if ($this->contains('uint') && Numbers::isUnsignedInt($value)) {
            return true;
        }

        // Additional checks for objects.
        if (is_object($value)) {
            // Any object type.
            if ($this->contains('object')) {
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

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Add/remove methods

    /**
     * Add types to the set.
     *
     * @param mixed ...$types The types to add to the set.
     * @return $this The modified set.
     */
    public function add(mixed ...$types): self
    {
        foreach ($types as $type) {
            // Check if the type is allowed in the set.
            if (!$this->isValid($type)) {
                throw new InvalidArgumentException("Invalid type: $type.");
            }

            // Add the type if new.
            if (!in_array($type, $this->types, true)) {
                $this->types[] = $type;
            }
        }

        // Return $this for chaining.
        return $this;
    }

    /**
     * Get the type name from a value and add it to the set.
     *
     * @param mixed $value The value to get the type name from.
     * @return $this The modified set.
     */
    public function addValueType(mixed $value): self
    {
        return $this->add(Type::getType($value));
    }

    /**
     * Remove one or more types from the set.
     *
     * @param mixed ...$types The types to remove from the set, if present.
     * @return $this The modified set.
     */
    public function remove(mixed ...$types): self
    {
        // No type check needed; if it's in the set, remove it.
        foreach ($types as $type) {
            $key = array_search($type, $this->types);
            if ($key === false) {
                continue;
            }
            unset($this->types[$key]);
        }

        // Reindex.
        $this->types = array_values($this->types);

        // Return $this for chaining.
        return $this;
    }

    /**
     * Remove all types from the set.
     *
     * @return $this
     */
    public function clear(): self
    {
        // Remove all the types.
        $this->types = [];

        // Return $this for chaining.
        return $this;
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Contains methods

    /**
     * Check if the set contains a given type.
     *
     * Strict checking is used, i.e. the type must match on value as well as type.
     *
     * @param mixed $type The type to check for.
     * @return bool
     */
    public function contains(mixed $type): bool
    {
        return in_array($type, $this->types, true);
    }

    /**
     * Check if the set contains one or more given types provided as an iterable.
     *
     * @param iterable $types The types to check for.
     * @return bool
     */
    public function containsAll(iterable $types): bool
    {
        foreach ($types as $type) {
            if (!$this->contains($type)) {
                return false;
            }
        }

        // If we got here, all types were found.
        return true;
    }

    /**
     * Check if the set contains any of the given types provided as an iterable.
     *
     * @param iterable $types The types to check for.
     * @return bool If the set contains any of the types.
     */
    public function containsAny(iterable $types): bool
    {
        foreach ($types as $it) {
            if ($this->contains($it)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the set is empty.
     *
     * @return bool True if the set is empty, false otherwise.
     */
    public function isEmpty(): bool
    {
        return empty($this->types);
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Countable implementation

    /**
     * Get the number of types in the set.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->types);
    }

    // endregion
}
