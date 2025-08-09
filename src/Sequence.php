<?php

declare(strict_types=1);

namespace Superclasses;

use ArrayAccess, Countable, IteratorAggregate, Traversable, ArrayIterator;
use InvalidArgumentException, BadFunctionCallException, UnderflowException;

include __DIR__ . '/TypeSet.php';

/**
 * A type-safe array implementation that enforces item types at runtime.
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
 * - 'object' - Any object instance
 * - 'resource' - Any resource type
 * - 'iterable' - Arrays, iterators, generators (anything iterable)
 * - 'callable' - Functions, methods, closures, invokables
 * 
 * ### Specific Types
 * - Class names: 'DateTime', 'MyClass' (includes inheritance)
 * - Interface names: 'Countable', 'JsonSerializable' (includes implementations)
 * - Resource types: 'stream', 'curl' (specific resource types)
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
 * - null → null
 * - mixed → null
 * - int → 0
 * - number → 0
 * - scalar → 0
 * - float → 0.0
 * - string → '' (empty string)
 * - bool → false
 * - array → [] (empty array)
 * 
 * All other types require an explicit default value to be provided.
 * 
 * @example Basic usage
 * $strings = new Sequence('string');
 * $strings->append('hello');
 * $strings[] = 'world';
 * 
 * @example Union types with custom default
 * $mixed = new Sequence('string|int', 'default');
 * $mixed->append('text');
 * $mixed->append(42);
 * 
 * @example Object types
 * $dates = new Sequence('DateTime', new DateTime());
 * $dates->append(new DateTime('tomorrow'));
 * 
 * @example Interface types
 * $countables = new Sequence('Countable', []);
 * $countables->append([1, 2, 3]);         // Arrays are countable
 * $countables->append(new ArrayObject()); // ArrayObject implements Countable
 */
class Sequence implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * The valid types for this sequence.
     * 
     * @var TypeSet
     */
    public private(set) TypeSet $types;

    /**
     * The items in the sequence.
     * 
     * @var array
     */
    public private(set) array $items = [];

    /**
     * The default value. This will have a type matching one of the allowed types.
     * 
     * @var mixed
     */
    public private(set) mixed $defaultValue;

    /**
     * Create a new Sequence with specified type constraints and a default value.
     * 
     * @param string|iterable $types Type specification (e.g., 'string', 'int|null', ['string', 'int']).
     * @param mixed $default_value Default value for new items. Auto-generated for basic types if omitted.
     * @throws InvalidArgumentException If no default can be generated or provided default is invalid.
     */
    public function __construct(string|iterable $types = 'mixed', mixed $default_value = null)
    {
        // Convert the types into a TypeSet.
        $this->types = $types instanceof TypeSet ? $types : new TypeSet($types);

        // If no types were specified, assume any are allowed.
        if (count($this->types) === 0) {
            $this->types->add('mixed');
        }

        // If a default value isn't specified, use sane defaults for common types.
        if (func_num_args() === 1) {
            if ($this->types->contains('null') || $this->types->contains('mixed')) {
                $default_value = null;
            } elseif ($this->types->contains('int') || $this->types->contains('number') || $this->types->contains('scalar')) {
                $default_value = 0;
            } elseif ($this->types->contains('float')) {
                $default_value = 0.0;
            } elseif ($this->types->contains('string')) {
                $default_value = '';
            } elseif ($this->types->contains('bool')) {
                $default_value = false;
            } elseif ($this->types->contains('array')) {
                $default_value = [];
            } else {
                throw new InvalidArgumentException("A default value must be provided for this item type.");
            }
        } elseif (!$this->types->match($default_value)) {
            // Check default value is valid for the specified types.
            throw new InvalidArgumentException("Default value has invalid type.");
        }

        // Set the default value.
        $this->defaultValue = $default_value;
    }

    /**
     * Construct a new sequence from an existing collection.
     */
    public static function fromIterable(iterable $src)
    {
        // Collect the item types.
        $types = new TypeSet();
        foreach ($src as $value) {
            $types->addValueType($value);
        }

        // Instantiate the sequence.
        $sequence = new self($types);

        // Copy the values into the new sequence.
        $sequence->append(...$src);

        return $sequence;
    }

    /**
     * Validate index parameters and optionally check bounds.
     * 
     * @param mixed $index The index to validate.
     * @param bool $check_lower_bound Whether to check if index is non-negative.
     * @param bool $check_upper_bound Whether to check if index is within array bounds.
     * @throws InvalidArgumentException If index is invalid or out of bounds.
     */
    public function checkIndex(
        mixed $index,
        bool $check_lower_bound = true,
        bool $check_upper_bound = true
    ) {
        // Check the index is valid.
        if (!is_int($index)) {
            throw new InvalidArgumentException("Index must be an integer.");
        }

        // Check the index isn't negative.
        if ($check_lower_bound && $index < 0) {
            throw new InvalidArgumentException("Index cannot be negative.");
        }

        // Check the index isn't too large.
        if ($check_upper_bound && $index >= count($this->items)) {
            throw new InvalidArgumentException("Index is out of range.");
        }
    }

    /**
     * Check if the item type is valid, and if not, throw an exception.
     */
    public function checkItemType(mixed $item): void
    {
        if (!$this->types->match($item)) {
            throw new InvalidArgumentException("The item type is invalid for this array.");
        }
    }

    /**
     * Get an item from the array.
     * 
     * @param int $index The zero-based index position of the value to get.
     * @return mixed The value at the given index.
     * @throws InvalidArgumentException If the index is not an integer or out of range.
     */
    public function get(int $index): mixed
    {
        $this->checkIndex($index);
        return $this->items[$index];
    }

    /**
     * Set an item to a given value by index.
     * 
     * If the index is out of range, the sequence will be increased in size to accommodate it.
     * Any intermediate positions will be filled with the default value.
     * 
     * If no item value is provided, the position will be set to the default value.
     * When an item value is provided, it must be compatible with the allowed types.
     *
     * @param int $index The zero-based index position to set.
     * @param mixed $item The value to set (optional). If omitted, uses the default value.
     * @throws InvalidArgumentException If the index is negative or the item type is invalid.
     * 
     * @example
     * $sequence = new Sequence('string', 'default');
     * $sequence->set(0, 'hello');  // Sets position 0 to 'hello'
     * $sequence->set(5);           // Sets position 5 to 'default', fills gaps with 'default'
     * $sequence->set(3, 'world');  // Sets position 3 to 'world'
     */
    public function set(int $index, mixed $item = null): void
    {
        // Check the index is valid.
        $this->checkIndex($index, check_upper_bound: false);

        // If the item is not specified, use default.
        if (func_num_args() == 1) {
            $item = $this->defaultValue;
        } else {
            // Check the item has a valid type.
            $this->checkItemType($item);
        }

        // Fill in any missing items with defaults.
        for ($i = count($this->items); $i < $index; $i++) {
            $this->items[$i] = $this->defaultValue;
        }

        // Set the item.
        $this->items[$index] = $item;
    }

    /**
     * Set an item to null.
     * This method does not remove an item from the sequence.
     * 
     * @param int $index The zero-based index position to unset.
     */
    public function unset(int $index): void
    {
        // Check the index is valid.
        $this->checkIndex($index);

        // Make sure nulls are allowed.
        if (!$this->types->contains('null') && !$this->types->contains('mixed')) {
            throw new BadFunctionCallException("Cannot unset an item if null is not an allowed type.");
        }

        // Set the item to null.
        $this->items[$index] = null;
    }

    /**
     * Add one or more items to the end of the array.
     * 
     * @param array $items The items to add to the sequence.
     * @throws InvalidArgumentException If any of the items have an invalid type.
     * 
     * @example
     * $sequence->append($new_item);
     * 
     * To add a collection of items to the sequence, use the splat operator.
     * @example
     * $sequence->append(...$new_items);
     */
    public function append(mixed ...$items): void
    {
        // Validate all items.
        foreach ($items as $item) {
            $this->checkItemType($item);
        }

        // Merge two arrays.
        $this->items = array_merge($this->items, $items);
    }

    /**
     * Add one or more items to the start of the array.
     * 
     * @param array $items The items to add to the sequence.
     * @throws InvalidArgumentException If any of the items have an invalid type.
     * 
     * @example
     * $sequence->prepend($new_item);
     * 
     * To add a collection of items to the sequence, use the splat operator.
     * @example
     * $sequence->prepend(...$new_items);
     */
    public function prepend(mixed ...$items): void
    {
        // Validate all items.
        foreach ($items as $item) {
            $this->checkItemType($item);
        }

        // Merge two arrays.
        $this->items = array_merge($items, $this->items);
    }

    /**
     * Remove an item from the array.
     * 
     * The indices of items at higher indices will be reduced by one, i.e. shifted down,
     * and the array length will be shortened by one.
     * 
     * @param int $index The zero-based index position of the item to remove.
     * @return mixed The removed value.
     * @throws InvalidArgumentException If the index is not an integer or out of range.
     */
    public function remove(int $index): mixed
    {
        // Ensure the index is valid.
        $this->checkIndex($index);

        // Get the item.
        $item = $this->items[$index];

        // Remove it from the array.
        array_splice($this->items, $index, 1);

        // Return the item.
        return $item;
    }

    /**
     * Remove all items matching a given value.
     * Strict equality is used to find matching values.
     * 
     * @param mixed $value The value to remove.
     * @return int The number of items removed.
     */
    public function removeByValue(mixed $value): int
    {
        // Get the number of items in the sequence.
        $originalCount = count($this->items);

        // Filter the sequence to remove the matching values.
        $this->items = array_values(array_filter(
            $this->items,
            fn($item) => $item !== $value
        ));

        // Return the number of items removed.
        return $originalCount - count($this->items);
    }

    /**
     * Remove the first item from the sequence.
     * 
     * @return mixed The removed item.
     * @throws UnderflowException If the sequence is empty.
     */
    public function removeFirst(): mixed
    {
        // Check for empty sequence.
        if (count($this->items) === 0) {
            throw new UnderflowException("No items in the sequence.");
        }

        // Remove and return the first item.
        return array_shift($this->items);
    }

    /**
     * Remove the last item from the sequence.
     * 
     * @return mixed The removed item.
     * @throws UnderflowException If the sequence is empty.
     */
    public function removeLast(): mixed
    {
        // Check for empty sequence.
        if (count($this->items) === 0) {
            throw new UnderflowException("No items in the sequence.");
        }

        // Remove and return the last item.
        return array_pop($this->items);
    }

    /**
     * Get a slice of the sequence.
     * 
     * The offset and length and parameters can be negative. They work the same as for array_slice().
     * @see https://www.php.net/manual/en/function.array-slice.php
     * 
     * @param int $offset The start position of the slice.
     * @param ?int $length The length of the slice.
     * @return Sequence The slice.
     */
    public function slice(int $offset, ?int $length): Sequence
    {
        // Initialize the result, preserving the original default value.
        $slice = new Sequence($this->types, $this->defaultValue);

        // Check for zero-length slice.
        if ($length === 0) {
            return $slice;
        }

        // Construct the result.
        $items = array_slice($this->items, $offset, $length);
        $slice->append(...$items);
        return $slice;
    }

    /**
     * Return a sequence with all items matching a certain filter.
     * 
     * @param callable $fn The filter function that returns true for items to keep.
     * @return Sequence A new sequence containing only the matching items.
     */
    public function filter(callable $fn): Sequence
    {
        // Get the matching values.
        $items = array_filter($this->items, fn($item) => $fn($item));

        // Construct the result.
        $result = new Sequence($this->types);
        $result->append(...$items);
        return $result;
    }

    /**
     * Find all indexes of items matching a specific value.
     * Strict equality is used to find matching values.
     * 
     * @param mixed $value The value to search for.
     * @return array An array of indexes where the value was found.
     */
    public function search(mixed $value): array
    {
        return array_keys(array_filter(
            $this->items,
            fn($item) => $item === $value
        ));
    }

    /**
     * Check if the sequence contains a specific value.
     * Strict equality is used to compare values.
     * No check is made on the provided value; if a value with an invalid type is provided then the
     * result will be false.
     * 
     * @param mixed $value The value to search for.
     * @return bool True if the value exists in the sequence, false otherwise.
     */
    public function contains(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    /**
     * Clear all items
     */
    public function clear(): void
    {
        $this->items = [];
    }

    /**
     * Check if the sequence is empty.
     * 
     * @return bool If the sequence is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // ArrayAccess implementation

    /**
     * Append or set a sequence item.
     * 
     * @param int $index The zero-based index position to set, or null for append.
     * @param mixed $item The value to set.
     * @throws InvalidArgumentException If the index or item type is invalid.
     */
    public function offsetSet(mixed $index, mixed $value): void
    {
        if ($index === null) {
            // $sequence[] = $value
            $this->append($value);
        } else {
            // $sequence[$key] = $value
            $this->set($index, $value);
        }
    }

    /**
     * Get a value from the sequence.
     * 
     * @param mixed $index The zero-based index position to set.
     * @return mixed The value at the specified index.
     * @throws InvalidArgumentException If the index is not an integer or out of range.
     */
    public function offsetGet(mixed $index): mixed
    {
        $this->checkIndex($index);
        return $this->items[$index];
    }

    /**
     * Check if a given index is valid.
     * 
     * @param mixed $index The zero-based index position to set.
     * @return bool If the given index exists in the sequence.
     */
    public function offsetExists(mixed $index): bool
    {
        return is_int($index) && $index >= 0 && $index < count($this->items);
    }

    /**
     * Set a sequence item to null.
     * 
     * This method does not remove an item from the sequence, as with an ordinary PHP array,
     * because with this data structure we want to maintain indices from 0 to sequence size - 1.
     * To remove an item from the sequence, use one of the remove*() methods.
     * 
     * @param mixed $index The zero-based index position to set.
     * @throws InvalidArgumentException If the index is not an integer or out of range.
     */
    public function offsetUnset(mixed $index): void
    {
        $this->unset($index);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Countable implementation

    /**
     * Get the number of items in the sequence.
     * 
     * @return int The number of items in the sequence.
     */
    public function count(): int
    {
        return count($this->items);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // IteratorAggregate implementation

    /**
     * Get iterator for foreach loops.
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
