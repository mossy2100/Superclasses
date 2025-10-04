<?php

declare(strict_types = 1);

namespace Superclasses\Collections;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Superclasses\Math\Numbers;
use Traversable;

class TypeSet implements Countable, IteratorAggregate
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
        // Convert a type string, including union type syntax (e.g. 'string|int'), into an array of type names.
        if (is_string($types)) {
            $types = explode('|', $types);
        }

        // Add types to the set.
        foreach ($types as $type) {
            // Check the type.
            if (!is_string($type)) {
                throw new InvalidArgumentException("Types must be provided as strings.");
            }

            // Trim just in case the user did something like 'string | int'.
            $type = trim($type);

            // Support the question mark nullable notation (e.g. '?string').
            if (strlen($type) > 1 && $type[0] === '?') {
                // Add null and the type being made nullable.
                $this->addType('null');
                $this->addType(substr($type, 1));
            }
            else {
                // This will throw if the type is invalid.
                $this->addType($type);
            }
        }
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Helper methods

    /**
     * Convert a string or iterable of types into a TypeSet, if necessary.
     *
     * @param string|iterable|self $types The value to convert.
     * @return self The converted TypeSet.
     */
    public static function toTypeSet(string|iterable|self $types = ''): self
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
     * @param string $type The type to check.
     * @return bool True if the type is a valid type, false otherwise.
     */
    public function isValid(string $type): bool
    {
        $type = trim($type);

        // Check for empty string.
        if ($type === '') {
            return false;
        }

        // Check for core types, pseudotypes, and generic "resource" and "object".
        $simple_types = [
            'null', 'int', 'float', 'string', 'bool', 'array', 'object', 'resource', 'callable',
            'iterable', 'mixed', 'scalar', 'number', 'uint'
        ];
        if (in_array($type, $simple_types, true)) {
            return true;
        }

        // Check for class names. Anonymous classes are not supported.
        $class = "[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*";
        if (preg_match("/^\\\\?({$class})(?:\\\\{$class})*$/", $type)) {
            return true;
        }

        // Check for resource types.
        if (preg_match("/^resource \([\w. ]+\)$/", $type)) {
            return true;
        }

        return false;
    }

    /**
     * Check if a value matches one of the types in the TypeSet.
     *
     * @see https://www.php.net/manual/en/function.get-debug-type.php
     *
     * @param mixed $value The value to check.
     * @return bool True if the value's type matches one of the types in the TypeSet, false otherwise.
     */
    public function match(mixed $value): bool
    {
        // Check if any type is allowed.
        if ($this->isEmpty() || $this->contains('mixed')) {
            return true;
        }

        // Check for a type or class name match.
        // This will match any strings returned by get_debug_type(), including "null", resource type strings like
        // "resource (stream)", and class names (including anonymous classes).
        // It will not match the old, longer type names like "integer", "double", or "boolean" (unsupported).
        // See get_debug_type() for more details.
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

        // Check iterable.
        if ($this->contains('iterable') && is_iterable($value)) {
            return true;
        }

        // Check callable.
        if ($this->contains('callable') && is_callable($value)) {
            return true;
        }

        // Check resource (unspecified type).
        if ($this->contains('resource') && is_resource($value)) {
            return true;
        }

        // Additional checks for objects.
        if (is_object($value)) {
            // Any object type.
            if ($this->contains('object')) {
                return true;
            }

            // Check value against parent classes, interfaces, and traits.
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

        return false;
    }

    /**
     * Try to get a sane default value for this type set.
     *
     * @param mixed $default_value The default value.
     * @return bool True if a default value was found, false otherwise.
     */
    public function tryGetDefaultValue(mixed &$default_value): bool
    {
        $result = true;
        if ($this->containsAny(['null', 'mixed'])) {
            $default_value = null;
        }
        elseif ($this->containsAny(['int', 'uint', 'number'])) {
            $default_value = 0;
        }
        elseif ($this->contains('float')) {
            $default_value = 0.0;
        }
        elseif ($this->contains('string')) {
            $default_value = '';
        }
        elseif ($this->contains('bool')) {
            $default_value = false;
        }
        elseif ($this->containsAny(['array', 'iterable'])) {
            $default_value = [];
        }
        else {
            $result = false;
        }

        return $result;
    }

    // endregion

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region Add methods

    /**
     * Add a type to the set.
     *
     * @param string $type The type to add to the set.
     * @return $this The modified set.
     * @throws InvalidArgumentException If the type is invalid (e.g. an empty string, '?', or an anonymous class).
     * @see TypeSet::isValid()
     */
    public function addType(string $type): self
    {
        // Ignore blanks.
        $type = trim($type);
        if ($type === '') {
            return $this;
        }

        // Check if the type string is valid.
        if (!$this->isValid($type)) {
            throw new InvalidArgumentException("Invalid type: $type.");
        }

        // Add the type if new.
        if (!in_array($type, $this->types, true)) {
            $this->types[] = $type;
        }

        // Return $this for chaining.
        return $this;
    }

    /**
     * Add multiple types to the set.
     *
     * @param iterable $types The types to add to the set.
     * @return self The modified set.
     */
    public function addTypes(iterable $types): self
    {
        // Add each type.
        foreach ($types as $type) {
            $this->addType($type);
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
        return $this->addType(get_debug_type($value));
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
     * Check if the set contains one or more given types.
     *
     * @param string|iterable|self $types The types to check for.
     * @return bool If the set contains all of the types.
     */
    public function containsAll(string|iterable|self $types): bool
    {
        // Convert to a TypeSet if necessary.
        $types = self::toTypeSet($types);

        // Check each.
        foreach ($types as $type) {
            if (!$this->contains($type)) {
                return false;
            }
        }

        // All of the specified types were found.
        return true;
    }

    /**
     * Check if the set contains any of the given types.
     *
     * @param string|iterable|self $types The types to check for.
     * @return bool If the set contains any of the types.
     */
    public function containsAny(string|iterable|self $types): bool
    {
        // Convert to a TypeSet if necessary.
        $types = self::toTypeSet($types);

        // Check each.
        foreach ($types as $it) {
            if ($this->contains($it)) {
                return true;
            }
        }

        // None of the specified types were found.
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

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region IteratorAggregate implementation

    /**
     * Get iterator for foreach loops.
     *
     * @return Traversable The iterator.
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->types);
    }

    // endregion
}
