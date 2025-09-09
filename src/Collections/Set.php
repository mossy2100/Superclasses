<?php

declare(strict_types = 1);

namespace Superclasses\Collections;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Stringable;
use Superclasses\Types\Type;
use Traversable;

/**
 * Implements a set of values.
 */
class Set implements Stringable, Countable, IteratorAggregate
{
    /**
     * Backing store for set items, implemented as a map of unique string key => original value.
     * This approach reduces the cost of checking for membership from O(n) to O(1).
     *
     * @var array<string, mixed>
     */
    private array $_items = [];

    /**
     * Items in the set.
     * Implemented as a getter-only virtual property with a backing field.
     *
     * @var list<mixed>
     */
    public array $items {
        get => $this->toArray();
    }

    /**
     * Constructor.
     *
     * @param iterable $items Items to add to the set.
     */
    public function __construct(iterable $items = [])
    {
        $this->addAll($items);
    }

    /**
     * Determine if an item is allowed to be added to this set.
     * This method is meant to be overridden in derived classes.
     *
     * @param mixed $item The item to check.
     * @return bool If the item is allowed to be added to the set.
     */
    protected function isItemAllowed(mixed $item): bool
    {
        return true;
    }

    /**
     * Return the set as an array.
     *
     * @return array The set as an array.
     */
    public function toArray(): array
    {
        return array_values($this->_items);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Methods for adding and removing members. These methods are mutating and return the calling object.

    /**
     * Add one item to the set.
     *
     * @param mixed $item The item to add to the set.
     * @return $this The modified set.
     */
    public function addOne(mixed $item): static
    {
        // Check if the item is allowed in the set.
        if (!$this->isItemAllowed($item)) {
            throw new InvalidArgumentException("This item is not allowed.");
        }

        // Add the item if new.
        $key = Type::getStringKey($item);
        if (!key_exists($key, $this->_items)) {
            $this->_items[$key] = $item;
        }

        // Return $this for chaining.
        return $this;
    }

    /**
     * Add multiple items to the set.
     *
     * @param iterable $items The items to add to the set.
     * @return $this The modified set.
     */
    public function addAll(iterable $items): static
    {
        // Add each item.
        foreach ($items as $item) {
            $this->addOne($item);
        }

        // Return $this for chaining.
        return $this;
    }

    /**
     * Add one or more items to the set.
     *
     * This is the general-purpose version of the method, which permits adding one or more items as a variable number of
     * arguments.
     *
     * @param mixed ...$items The items to add to the set.
     * @return $this The modified set.
     */
    public function add(mixed ...$items): static
    {
        // Add the items.
        return $this->addAll($items);
    }

    /**
     * Remove an item from the set.
     *
     * @param mixed $item_to_remove The item to remove from the set, if present.
     * @return $this The modified set.
     */
    public function removeOne(mixed $item_to_remove): static
    {
        return $this->remove($item_to_remove);
    }

    /**
     * Remove one or more items from the set, provided as an iterable.
     *
     * @param iterable $items_to_remove The items to remove from the set, if present.
     * @return $this The modified set.
     */
    public function removeAll(iterable $items_to_remove): static
    {
        // No type check needed; if it's in the set, remove it.
        foreach ($items_to_remove as $item) {
            $key = Type::getStringKey($item);
            if (key_exists($key, $this->_items)) {
                unset($this->_items[$key]);
            }
        }

        // Return $this for chaining.
        return $this;
    }

    /**
     * Remove one or more items from the set.
     *
     * @param mixed ...$items_to_remove The items to remove from the set, if present.
     * @return $this The modified set.
     */
    public function remove(mixed ...$items_to_remove): static
    {
        return $this->removeAll($items_to_remove);
    }

    /**
     * Remove all items from the set.
     *
     * @return $this
     */
    public function clear(): static
    {
        // Remove all the items.
        $this->_items = [];

        // Return $this for chaining.
        return $this;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // SetOf operations. These are non-mutating and return new sets.

    /**
     * Return the union of this set and another set.
     *
     * @param self $other The set to union with.
     * @return static A new set equal to the union of the two sets.
     */
    public function union(self $other): static
    {
        $out = new static();

        // Left-biased union: values from $this take precedence on key collision.
        $out->_items = $this->_items + $other->_items;

        // Return the new set.
        return $out;
    }

    /**
     * Return the intersection of this set and another set.
     *
     * @param self $other The set to intersect with.
     * @return static A new set equal to the intersection of the two sets.
     */
    public function intersect(self $other): static
    {
        $out = new static();

        // Add items present in both sets.
        foreach ($this->_items as $k => $v) {
            if (key_exists($k, $other->_items)) {
                $out->_items[$k] = $v;
            }
        }

        // Return the new set.
        return $out;
    }

    /**
     * Return the difference of this set and another set.
     *
     * @param self $other The set to subtract from.
     * @return static A new set equal to the difference of the two sets.
     */
    public function diff(self $other): static
    {
        $out = new static();

        // Add items present in this set that are not present in the other set.
        foreach ($this->_items as $k => $v) {
            if (!key_exists($k, $other->_items)) {
                $out->_items[$k] = $v;
            }
        }

        // Return the new set.
        return $out;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Inspection and comparison methods. These all return booleans.

    /**
     * Check if the set contains a given item.
     *
     * Strict checking is used, i.e. the item must match on value as well as type.
     *
     * @param mixed $item The item to check for.
     * @return bool
     */
    public function containsOne(mixed $item): bool
    {
        $key = Type::getStringKey($item);
        return key_exists($key, $this->_items);
    }

    /**
     * Check if the set contains one or more given items provided as an iterable.
     *
     * @param iterable $items The items to check for.
     * @return bool
     */
    public function containsAll(iterable $items): bool
    {
        foreach ($items as $item) {
            if (!$this->containsOne($item)) {
                return false;
            }
        }

        // If we got here, all items were found.
        return true;
    }

    /**
     * Check if the set contains one or more given items
     *
     * Strict checking is used, i.e. the item must match on value as well as type.
     *
     * @param mixed ...$items The items to check for.
     * @return bool
     */
    public function contains(mixed ...$items): bool
    {
        return $this->containsAll($items);
    }

    /**
     * Check if the set contains any of the given items provided as an iterable.
     *
     * @param iterable $items The items to check for.
     * @return bool If the set contains any of the items.
     */
    public function containsAny(iterable $items): bool
    {
        foreach ($items as $it) {
            if ($this->containsOne($it)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the set contains none of the given items provided as an iterable.
     *
     * @param iterable $items The items to check for.
     * @return bool If the set contains none of the items.
     */
    public function containsNone(iterable $items): bool
    {
        foreach ($items as $it) {
            if ($this->containsOne($it)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks if a set is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Checks if two sets are equal (the same type and contain the same elements).
     *
     * @param self $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return ($this::class === $other::class) && ($this->count() === $other->count()) && $this->subset($other);
    }

    /**
     * Checks if a set is a subset of another set.
     *
     * They must be objects of the same class, but they can have different allowed types.
     *
     * @param self $other
     * @return bool If $this is a subset of $other.
     */
    public function subset(self $other): bool
    {
        return ($this::class === $other::class) && array_all($this->_items, fn($item) => $other->contains($item));
    }

    /**
     * Checks if a set is a proper subset of another set.
     *
     * @param self $other
     * @return bool
     */
    public function properSubset(self $other): bool
    {
        return ($this->count() < $other->count()) && $this->subset($other);
    }

    /**
     * Checks if a set is a superset of another set.
     *
     * @param self $other
     * @return bool
     */
    public function superset(self $other): bool
    {
        return $other->subset($this);
    }

    /**
     * Checks if a set is a proper superset of another set.
     *
     * @param self $other
     * @return bool
     */
    public function properSuperset(self $other): bool
    {
        return $other->properSubset($this);
    }

    /**
     * Checks if two sets are disjoint (have no elements in common).
     *
     * @param self $other The set to compare with.
     * @return bool True if the sets are disjoint; false otherwise.
     */
    public function disjoint(self $other): bool
    {
        return array_all($this->_items, fn($item) => !$other->contains($item));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Stringable implementation

    /**
     * Generate a string representation of the set.
     *
     * @return string
     */
    public function __toString(): string
    {
        return '{' . implode(', ', array_map('Stringify\stringify', $this->_items)) . '}';
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Countable implementation

    /**
     * Get the number of items in the set.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->_items);
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
        return new ArrayIterator($this->toArray());
    }
}
