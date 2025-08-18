<?php

declare(strict_types = 1);

namespace Superclasses;

use Override;

/**
 * SetOf class.
 * Unlike Set, which allows members of any type, this class allows you to specify what types the members must have.
 * It is intended to be equivalent to SetOf<T> in Java or C#.
 */
class SetOf extends Set
{
    /**
     * Valid types for items in the set.
     *
     * @var TypeSet
     */
    public protected(set) TypeSet $types;

    /**
     * Constructor.
     *
     * Creates a new set that only permits values of the specified types.
     */
    public function __construct(string|iterable $types)
    {
        parent::__construct();
        $this->types = $types instanceof TypeSet ? $types : new TypeSet($types);
    }

    /**
     * Helper function used by add() to determine if an item can be added to the set.
     *
     * @param mixed $item The item to check.
     * @return bool True if the item is allowed, false otherwise.
     */
    #[Override]
    protected function isItemAllowed(mixed $item): bool {
        // Check the item type.
        return $this->types->match($item);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Set operations. These methods return new sets.

    /**
     * Union of two sets.
     *
     * The resulting set will allow the types allowed by both sets.
     *
     * @param Set $other The other set with items to include.
     * @return static A new set with elements from $this and $other.
     */
    #[Override]
    public function union(Set $other): static
    {
        // Need to allow types from both sets.
        $types = $this->types;
        if ($other instanceof self) {
            $types = $types->union($other->types);
        }

        // Construct the new set.
        $result = new self($types);
        $result->addAll($this->items);
        $result->addAll($other->items);
        return $result;
    }

    /**
     * Intersection between two sets.
     *
     * The resulting set will allow the same types as the $this set.
     *
     * @param Set $other The other set to intersect the $this set with.
     * @return static A new set with elements common to $this and $other.
     */
    #[Override]
    public function intersect(Set $other): static
    {
        // Assume the result set has the same type constraints as this set.
        $out = new self($this->types);

        // Add items present in both sets.
        foreach ($this->items as $v) {
            if ($other->containsOne($v)) {
                $out->addOne($v);
            }
        }

        // Return the new set.
        return $out;
    }

    /**
     * Difference between two sets.
     *
     * The resulting set will allow the same types as the $this set.
     *
     * @param Set $other The set with items to remove.
     * @return static A new set with elements from $this that are not in $other.
     */
    #[Override]
    public function diff(Set $other): static
    {
        // Assume the result set has the same type constraints as this set.
        $out = new self($this->types);

        // Add items present in this set that are not present in the other set.
        foreach ($this->items as $v) {
            if (!$other->containsOne($v)) {
                $out->addOne($v);
            }
        }

        // Return the new set.
        return $out;
    }
}
