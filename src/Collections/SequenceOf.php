<?php

declare(strict_types = 1);

namespace Superclasses\Collections;

use InvalidArgumentException;
use OutOfRangeException;
use Override;

/**
 * A type-safe list implementation that enforces item types at runtime.
 *
 * Supports union types and provides array-like access through ArrayAccess and Iterator interfaces.
 * Automatically generates sensible defaults for basic types or allows explicit default values.
 *
 * ## Supported Type Specifications
 *
 * ### Basic Types
 * - 'string' - String values
 * - 'int' - Integer values
 * - 'float' - Floating point values
 * - 'bool' - Boolean values (true/false)
 * - 'null' - Null values
 * - 'array' - Array values
 *
 * ### Pseudo-types
 * - 'mixed' - Any type (no restrictions)
 * - 'scalar' - Any scalar type (string|int|float|bool)
 * - 'number' - Either number type (int|float)
 * - 'uint'   - Unsigned integer type (int where value >= 0)
 * - 'object' - Any object instance
 * - 'resource' - Any resource type
 * - 'iterable' - Arrays, iterators, generators (anything iterable)
 * - 'callable' - Functions, methods, closures, invokables
 *
 * ### Specific Types
 * - Class names: 'DateTime', 'MyClass' (includes inheritance)
 * - Interface names: 'Countable', 'JsonSerializable' (includes implementations)
 * - Resource types: 'resource (stream)', 'resource (curl)', etc.
 *
 * ### Union Types
 * Use pipe syntax to allow multiple types:
 * - 'string|int' - String OR integer values
 * - 'array|object' - Array OR object values
 * - 'DateTime|null' - DateTime objects OR null
 *
 * ### Nullable Types
 * Use question mark syntax for nullable types:
 * - '?string' - Equivalent to 'string|null'
 * - '?DateTime' - Equivalent to 'DateTime|null'
 *
 * ## Automatic Defaults
 * When no explicit default is provided, the following defaults are used:
 * - null or mixed → null
 * - int, uint, number, or scalar → 0
 * - float → 0.0
 * - string → '' (empty string)
 * - bool → false
 * - array → [] (empty array)
 *
 * All other types require an explicit default value to be provided.
 *
 * @example Basic usage
 * $strings = new SequenceOf('string');
 * $strings->append('hello');
 * $strings[] = 'world';
 *
 * @example Union types with custom default
 * $mixed = new SequenceOf('string|int', 'default');
 * $mixed->append('text');
 * $mixed->append(42);
 *
 * @example Object types
 * $dates = new SequenceOf('DateTime', new DateTime());
 * $dates->append(new DateTime('tomorrow'));
 *
 * @example Interface types
 * $countables = new SequenceOf('Countable', []);
 * $countables->append([1, 2, 3]);         // Arrays are countable
 * $countables->append(new ArrayObject()); // ArrayObject implements Countable
 */
class SequenceOf extends Sequence
{
    /**
     * The valid types for this sequence.
     *
     * @var TypeSet
     */
    private(set) TypeSet $types;

    /**
     * Create a new SequenceOf with specified type constraints and a default value.
     *
     * @param string|iterable $types Type specification (e.g., 'string', 'int|null', ['string', 'int']).
     *      The default is 'auto', which means determine the types from the source iterable and default value.
     * @param iterable $src The source iterable (default empty array).
     * @param mixed $default_value Default value for new items. Auto-generated for basic types if omitted.
     * @throws InvalidArgumentException If no default can be generated or provided default is invalid.
     */
    public function __construct(string|iterable $types = 'auto', iterable $src = [], mixed $default_value = null)
    {
        // Call the parent constructor.
        parent::__construct($src, $default_value);

        // If 'auto' types were specified, infer them from the source items and default value.
        if ($types === 'auto') {
            $this->types = new TypeSet();

            // Add types from the source iterable.
            foreach ($src as $item) {
                $this->types->addValueType($item);
            }

            // Add the default value type.
            $this->types->addValueType($this->defaultValue);
        }
        else {
            // Convert the types into a TypeSet.
            $this->types = $types instanceof TypeSet ? $types : new TypeSet($types);
        }

        // If a default value isn't specified, use sane defaults for common types.
        if (func_num_args() === 2) {
            // Try to determine a sane default value.
            if (!$this->types->tryGetDefaultValue($default_value)) {
                throw new InvalidArgumentException("A default value must be provided (or allow nulls).");
            }
            $this->defaultValue = $default_value;
        } elseif (!$this->types->match($default_value)) {
            // Default value is invalid for the specified type set.
            throw new InvalidArgumentException("Default value has an invalid type.");
        }
    }

    /**
     * Check if the item type is valid, and if not, throw an exception.
     *
     * @param mixed $item The item to check.
     * @throws InvalidArgumentException If the item type is invalid.
     */
    public function checkItemType(mixed $item): void
    {
        if (!$this->types->match($item)) {
            throw new InvalidArgumentException("The item type is invalid for this sequence.");
        }
    }

    /**
     * Add one or more items to the end of the array.
     *
     * @param mixed ...$items The items to add to the sequence.
     * @return $this The sequence instance.
     * @throws InvalidArgumentException If any of the items have an invalid type.
     *
     * @example
     * $sequence->append($new_item);
     *
     * To add a collection of items to the sequence, use the splat operator.
     * @example
     * $sequence->append(...$new_items);
     */
    #[Override]
    public function append(mixed ...$items): static
    {
        // Validate all items.
        foreach ($items as $item) {
            $this->checkItemType($item);
        }

        // Call the parent method.
        return parent::append(...$items);
    }

    /**
     * Add one or more items to the start of the array.
     *
     * @param mixed ...$items The items to add to the sequence.
     * @return $this The sequence instance.
     * @throws InvalidArgumentException If any of the items have an invalid type.
     *
     * @example
     * $sequence->prepend($new_item);
     *
     * To add a collection of items to the sequence, use the splat operator.
     * @example
     * $sequence->prepend(...$new_items);
     */
    #[Override]
    public function prepend(mixed ...$items): static
    {
        // Validate all items.
        foreach ($items as $item) {
            $this->checkItemType($item);
        }

        // Call the parent method.
        return parent::prepend(...$items);
    }

    /**
     * Get a slice of the sequence.
     *
     * The index and length parameters can be negative. They work the same as for array_slice().
     * @see https://www.php.net/manual/en/function.array-slice.php
     *
     * @param int $index The start position of the slice.
     *      If non-negative, the slice will start at that index in the sequence.
     *      If negative, the slice will start that far from the end of the sequence.
     * @param ?int $length The length of the slice.
     *      If given and is positive, the sequence will have up to that many elements in it.
     *      If the sequence is shorter than the length, only the available items will be present.
     *      If given and is negative, the slice will stop that many elements from the end of the sequence.
     *      If omitted or null, the slice will have everything from index up until the end of the sequence.
     * @return static The slice.
     */
    #[Override]
    public function slice(int $index, ?int $length = null): static
    {
        // Call the parent method.
        $seq = parent::slice($index, $length);

        // Clone the type set.
        $seq->types = clone $this->types;

        return $seq;
    }

    /**
     * Return a sequence with all items matching a certain filter.
     *
     * @param callable $fn The filter function that returns true for items to keep.
     * @return static A new sequence containing only the matching items.
     */
    #[Override]
    public function filter(callable $fn): static
    {
        // Call the parent method.
        $seq = parent::filter($fn);

        // Clone the type set.
        $seq->types = clone $this->types;

        return $seq;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // region ArrayAccess implementation

    /**
     * Append or set a sequence item.
     *
     * If the index is out of range, the sequence will be increased in size to accommodate it.
     * Any intermediate positions will be filled with the default value.
     * NB: If the default is an object, all items set to the default will reference the same object.
     *
     * The provided value must be compatible with the allowed types.
     *
     * @param int $offset The zero-based index position to set, or null to append.
     * @param mixed $value The value to set.
     * @throws InvalidArgumentException If the index is not null or an integer, or if the item type is not allowed.
     * @throws OutOfRangeException If the index is out of range.
     */
    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Check the item has a valid type.
        $this->checkItemType($value);

        // Call the parent implementation.
        parent::offsetSet($offset, $value);
    }

    /**
     * Set a sequence item to null, if allowed. If not, an exception will be thrown.
     *
     * This method does not remove an item from the sequence, as with an ordinary PHP array,
     * because with this data structure we want to maintain indices from 0 up to the sequence size minus 1.
     * To remove an item from the sequence, use one of the remove*() methods.
     *
     * @param mixed $offset The zero-based index position to set.
     * @throws OutOfRangeException If the index is outside the range of valid indices for the sequence.
     * @throws InvalidArgumentException If null is not an allowed type for this sequence.
     */
    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        // Check the index is valid.
        $this->checkIndex($offset);

        // Make sure nulls are allowed.
        if (!$this->types->contains('null') && !$this->types->contains('mixed')) {
            throw new InvalidArgumentException("Cannot unset an item if null is not an allowed type.");
        }

        // Set the item to null.
        $this->items[$offset] = null;
    }

    // endregion
}
