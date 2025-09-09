<?php

declare(strict_types = 1);

namespace Superclasses\Collections;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;
use Throwable;
use InvalidArgumentException;
use UnderflowException;
use OutOfRangeException;

/**
 * A sequence implementation that is stricter than ordinary PHP arrays.
 * Offsets are always sequential integers starting from 0. Therefore, the largest offset (a.k.a. index or key) will
 * equal the number of items in the sequence minus 1.
 * Sequence items can be set at positions beyond the current range, but intermediate items will be filled in with a
 * default value, specified in the constructor.
 */
class Sequence implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * The items in the sequence.
     *
     * @var array
     */
    public protected(set) array $items = [];

    /**
     * The default value.
     *
     * @var mixed
     */
    public mixed $defaultValue;

    /**
     * Create a new sequence, optionally from an existing iterable.
     *
     * A default value may be specified, used to fill gaps when increasing the sequence length.
     * @see self::offsetSet()
     *
     * @param iterable $src The source iterable (default empty array).
     * @param mixed $default_value Default value for new items (default null).
     */
    public function __construct(iterable $src = [], mixed $default_value = null)
    {
        // Copy values from the source iterable into the new sequence.
        foreach ($src as $item) {
            $this->append($item);
        }

        // Set the default value.
        $this->defaultValue = $default_value;
    }

    /**
     * Validate offset parameters and optionally check bounds.
     *
     * @param mixed $offset The offset to validate.
     * @param bool $check_lower_bound Whether to check if an offset is non-negative.
     * @param bool $check_upper_bound Whether to check if an offset is within array bounds.
     * @throws InvalidArgumentException If the offset is not an integer.
     * @throws OutOfRangeException If the offset is outside the valid range for the sequence.
     */
    public function checkOffset(mixed $offset, bool $check_lower_bound = true, bool $check_upper_bound = true): void {
        // Check the offset is an integer.
        if (!is_int($offset)) {
            throw new InvalidArgumentException("Offset must be an integer.");
        }

        // Check the offset isn't negative.
        if ($check_lower_bound && $offset < 0) {
            throw new OutOfRangeException("Offset cannot be negative.");
        }

        // Check the offset isn't too large.
        if ($check_upper_bound && $offset >= count($this->items)) {
            throw new OutOfRangeException("Offset is out of range.");
        }
    }

    /**
     * Get the first item from the sequence.
     *
     * @return mixed The first item.
     * @throws OutOfRangeException If the sequence is empty.
     */
    public function first(): mixed {
        return $this[0];
    }

    /**
     * Get the last item from the sequence.
     *
     * @return mixed The last item.
     * @throws OutOfRangeException If the sequence is empty.
     */
    public function last(): mixed {
        return $this[array_key_last($this->items)];
    }

    /**
     * Add one or more items to the end of the sequence.
     *
     * NB: This is a mutating method.
     *
     * @param mixed ...$items The items to add to the sequence.
     *
     * @example
     * $sequence->append($item);
     * $sequence->append($item1, $item2, $item3);
     * $sequence->append(...$items);
     */
    public function append(mixed ...$items): void
    {
        // Loop instead of using array_push() to avoid array copy.
        foreach ($items as $item) {
            $this->items[] = $item;
        }
    }

    /**
     * Add one or more items to the start of the sequence.
     *
     * @param mixed ...$items The items to add to the sequence.
     *
     * @example
     * $sequence->prepend($item);
     * $sequence->prepend($item1, $item2, $item3);
     * $sequence->prepend(...$items);
     */
    public function prepend(mixed ...$items): void
    {
        array_unshift($this->items, ...$items);
    }

    /**
     * Remove the item at the given offset from the sequence.
     *
     * The offsets of items at higher offsets than the one specified by $offset will be reduced by 1, i.e. shifted down,
     * and the sequence length will be reduced by 1.
     *
     * @param int $offset The zero-based offset position of the item to remove.
     * @return mixed The removed value.
     * @throws InvalidArgumentException If the offset is not an integer.
     * @throws OutOfRangeException If the offset is outside the valid range for the sequence.
     */
    public function remove(int $offset): mixed
    {
        // Ensure the offset is valid.
        $this->checkOffset($offset);

        // Get the item.
        $item = $this->items[$offset];

        // Remove it from the sequence.
        array_splice($this->items, $offset, 1);

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
            fn ($item) => $item !== $value
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
        // Check for an empty sequence.
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
        // Check for an empty sequence.
        if (count($this->items) === 0) {
            throw new UnderflowException("No items in the sequence.");
        }

        // Remove and return the last item.
        return array_pop($this->items);
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
        return $this->items === [];
    }

    /**
     * Get a slice of the sequence.
     *
     * Both the offset and the length can be negative. They work the same as for array_slice().
     * @see https://www.php.net/manual/en/function.array-slice.php
     *
     * @param int $offset The start position of the slice.
     *      If non-negative, the slice will start at that offset in the sequence.
     *      If negative, the slice will start that far from the end of the sequence.
     * @param ?int $length The length of the slice.
     *      If given and is positive, then the sequence will have up to that many elements in it.
     *      If the sequence is shorter than the length, then only available items will be present.
     *      If given and is negative, the slice will stop that many elements from the end of the sequence.
     *      If omitted or null, then the slice will have everything from offset up until the end of the sequence.
     * @return static The slice.
     */
    public function slice(int $offset, ?int $length = null): static
    {
        // Get the items.
        $items = array_slice($this->items, $offset, $length);

        // Construct the result.
        return new static($items, $this->defaultValue);
    }

    /**
     * Searches the array for a given value and returns the first corresponding key if successful.
     *
     * This method is analogous to array_search().
     * @see https://www.php.net/manual/en/function.array-search.php
     *
     * @param mixed $value The value to search for.
     * @return int|null The offset of the first matching value, or null if the value is not found.
     */
    public function search(mixed $value): ?int
    {
        return array_search($value, $this->items, true);
    }

    /**
     * Check if the sequence contains a specific value.
     * Strict equality is used to compare values.
     *
     * @param mixed $value The value to search for.
     * @return bool True if the value exists in the sequence, false otherwise.
     */
    public function contains(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    /**
     * Check if all items in the sequence pass a test.
     *
     * This method is analogous to array_all().
     * @see https://www.php.net/manual/en/function.array-all.php
     *
     * @param callable $fn The test function.
     * @return bool True if all items pass the test, false otherwise.
     */
    public function all(callable $fn): bool
    {
        return array_all($this->items, $fn);
    }

    /**
     * Check if any items in the sequence pass a test.
     *
     * This method is analogous to array_any().
     * @see https://www.php.net/manual/en/function.array-any.php
     *
     * @param callable $fn The test function.
     * @return bool True if any items pass the test, false otherwise.
     */
    public function any(callable $fn): bool
    {
        return array_any($this->items, $fn);
    }

    /**
     * Split the sequence into chunks of a given size.
     * The last chunk may be smaller than the specified size.
     *
     * This method is analogous to array_chunk().
     * @see https://www.php.net/manual/en/function.array-chunk.php
     *
     * @param int $size The size of each chunk.
     * @return static[] An array of chunks.
     */
    public function chunk(int $size): array {
        $chunks = array_chunk($this->items, $size);
        $result = [];
        foreach ($chunks as $chunk) {
            $result[] = new static($chunk, $this->defaultValue);
        }
        return $result;
    }

    /**
     * Counts the occurrences of each distinct value in a sequence.
     *
     * This method is analogous to array_count_values().
     * @see https://www.php.net/manual/en/function.array-count-values.php
     *
     * @return Dictionary A dictionary mapping values to the number of occurrences.
     */
    public function countValues(): Dictionary
    {
        $value_count = new Dictionary();
        foreach ($this->items as $item) {
            if ($value_count->hasKey($item)) {
                $value_count[$item]++;
            } else {
                $value_count[$item] = 1;
            }
         }
         return $value_count;
    }

    /**
     * Fill the sequence with a given value.
     *
     * This method is analogous to array_fill().
     * @see https://www.php.net/manual/en/function.array-fill.php
     *
     * @param int $start_offset The zero-based offset position to start filling.
     * @param int $count The number of items to fill.
     * @param mixed $value The value to fill with.
     */
    public function fill(int $start_offset, int $count, mixed $value): void {
        for ($i = 0; $i < $count; $i++) {
            $this[$start_offset + $i] = $value;
        }
    }

    /**
     * Return a sequence with all items matching a certain filter.
     *
     * This method is analogous to array_filter().
     * @see https://www.php.net/manual/en/function.array-filter.php
     *
     * @param callable $fn The filter function that returns true for items to keep.
     * @return static A new sequence containing only the matching items.
     */
    public function filter(callable $fn): static
    {
        // Get the matching values.
        $items = array_filter($this->items, fn($item) => $fn($item));

        // Construct the result.
        return new static($items, $this->defaultValue);
    }

    /**
     * Returns the first element satisfying a callback function.
     *
     * This method is analogous to array_find().
     * @see https://www.php.net/manual/en/function.array-find.php
     *
     * @param callable $fn The filter function that will return true for a matching item.
     * @return mixed The value of the first element for which the callback returns true. If no matching element is found
     *      the function returns null.
     */
    public function find(callable $fn): mixed
    {
        return array_find($this->items, $fn);
    }

    /**
     * Applies the callback to the items in the sequence.
     *
     * @param callable $fn The callback function to apply to each item.
     * @return static A new sequence containing the results of the callback function.
     */
    public function map(callable $fn): static
    {
        $items = array_map($fn, $this->items);
        return new static($items, $this->defaultValue);
    }

    /**
     * Return a new sequence with the same items as the $this sequence but in reverse order.
     *
     * @return static A new sequence with the same items as the $this sequence but in reverse order.
     */
    public function reverse(): static
    {
        $items = array_reverse($this->items);
        return new static($items, $this->defaultValue);
    }

    /**
     * Return a new sequence with the items sorted in ascending order.
     *
     * This method is analogous to sort(), except that it's non-mutating.
     * @see https://www.php.net/manual/en/function.sort.php
     *
     * @param int $flags The sorting flags.
     * @return static
     */
    public function sort(int $flags = SORT_REGULAR): static
    {
        // Copy the items array so the method is non-mutating.
        $items = $this->items;
        sort($items, $flags);
        return new static($items, $this->defaultValue);
    }

    /**
     * Return a new sequence with the items sorted in descending order.
     *
     * This method is analogous to rsort(), except that it's non-mutating.
     * @see https://www.php.net/manual/en/function.rsort.php
     *
     * @param int $flags The sorting flags.
     * @return static
     */
    public function sortReverse(int $flags = SORT_REGULAR): static
    {
        // Copy the items array so the method is non-mutating.
        $items = $this->items;
        rsort($items, $flags);
        return new static($items, $this->defaultValue);
    }

    /**
     * Return a new sequence with the items sorted using a custom comparison function.
     *
     * This method is analogous to usort(), except that it's non-mutating.
     * @see https://www.php.net/manual/en/function.usort.php
     *
     * @param callable $fn The comparison function.
     * @return static
     */
    public function sortBy(callable $fn): static
    {
        // Copy the items array so the method is non-mutating.
        $items = $this->items;
        usort($items, $fn);
        return new static($items, $this->defaultValue);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // ArrayAccess implementation

    /**
     * Append or set a sequence item.
     *
     * If the offset is out of range, the sequence will be increased in size to accommodate it.
     * Any intermediate positions will be filled with the default value.
     * NB: If the default is an object, all items set to the default will reference the same object.
     * If you don't want this behaviour, set each sequence item individually.
     *
     * @param mixed $offset The zero-based offset position to set, or null to append.
     * @param mixed $value The value to set.
     * @throws InvalidArgumentException If the offset is not null or an integer.
     * @throws OutOfRangeException If the offset is out of range.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            // Append a new item to the sequence.
            // $sequence[] = $value
            $this->append($value);
        } else {
            // Update an item.
            // $sequence[$key] = $value

            // Check the offset is valid.
            $this->checkOffset($offset, check_upper_bound: false);

            // Fill in any missing items with defaults.
            $start = count($this->items);
            for ($i = $start; $i < $offset; $i++) {
                $this->items[$i] = $this->defaultValue;
            }

            // Set the item value.
            $this->items[$offset] = $value;
        }
    }

    /**
     * Get a value from the sequence.
     *
     * @param mixed $offset The zero-based offset position to set.
     * @return mixed The value at the specified offset.
     * @throws InvalidArgumentException If the offset is not an integer.
     * @throws OutOfRangeException If the offset is outside the valid range for the sequence.
     */
    public function offsetGet(mixed $offset): mixed
    {
        // Check the offset is valid.
        $this->checkOffset($offset);

        // Get the item at the specified offset.
        return $this->items[$offset];
    }

    /**
     * Check if a given offset is valid.
     *
     * @param mixed $offset The sequence offset position.
     * @return bool If the given offset is an integer and within the current valid range for the sequence.
     */
    public function offsetExists(mixed $offset): bool
    {
        try {
            // Leverage existing method.
            $this->checkOffset($offset);
            return true;
        }
        catch (Throwable) {
            return false;
        }
    }

    /**
     * Set a sequence item to null.
     *
     * This method isn't usually called as a method, but rather as a result of calling unset($sequence[$offset]).
     *
     * Doing this doesn't remove an item from the sequence, as it does with ordinary PHP arrays. This is because this
     * data structure maintains zero-indexed sequential keys. Therefore, removing an item from the sequence would
     * require re-indexing later items. This could be unexpected behavior.
     *
     * To remove an item from the sequence, use one of the remove*() methods.
     *
     * @param mixed $offset The zero-based offset position to unset.
     * @throws OutOfRangeException If the offset is outside the valid range for the sequence.
     */
    public function offsetUnset(mixed $offset): void
    {
        // Check the offset is valid.
        $this->checkOffset($offset);

        // Set the item to null.
        $this->items[$offset] = null;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // IteratorAggregate implementation

    /**
     * Get iterator for foreach loops.
     *
     * @return Traversable The iterator.
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
