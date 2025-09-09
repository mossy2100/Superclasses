<?php

declare(strict_types = 1);

namespace Superclasses\Collections;

use ArrayAccess;
use Countable;
use Traversable;
use IteratorAggregate;
use OutOfBoundsException;
use Superclasses\Types\Type;

/**
 * Dictionary class that permits keys and values of any type, including scalar, complex, nullable,
 * and union types.
 *
 * @example
 * $customers = new Dictionary('int', 'Customer');
 * $sales_data = new Dictionary('DateTime', 'float');
 * $country_codes = new Dictionary('string', 'string');
 * $car_make = new Dictionary('string', '?string');
 */
class Dictionary implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * Array of key-value pairs in the dictionary.
     *
     * @var KeyValuePair[]
     */
    private array $_items = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Construct a new Dictionary from an existing collection.
     *
     * @param iterable $src The source collection.
     * @return static The new dictionary.
     */
    public static function fromIterable(iterable $src): static
    {
        // Instantiate the Dictionary (or DictionaryOf, or other subclass).
        $dict = new static();

        // Copy the values into the new dictionary.
        foreach ($src as $key => $value) {
            // Leverages offsetSet() to generate the key-value pair.
            $dict[$key] = $value;
        }

        return $dict;
    }

    /**
     * Get all the keys as an array.
     */
    public function keys(): array
    {
        return array_column($this->_items, 'key');
    }

    /**
     * Get all the values as an array.
     */
    public function values(): array
    {
        return array_column($this->_items, 'value');
    }

    /**
     * Get all the key-value pairs as an array.
     */
    public function entries(): array
    {
        return array_values($this->_items);
    }

    /**
     * Clear all items
     */
    public function clear(): void
    {
        $this->_items = [];
    }

    /**
     * Check if the dictionary is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->_items);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // ArrayAccess implementation

    /**
     * SetOf an item by key.
     *
     * If a key is in use, the corresponding key-value pair will be replaced.
     * If not, a new key-value pair will be added to the dictionary.
     *
     * @param mixed $offset The key to set.
     * @param mixed $value The value to set.
     *
     * @throws OutOfBoundsException If no key or a null key is specified.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Check if a key was specified.
        if ($offset === null) {
            throw new OutOfBoundsException("A key must be specified.");
        }

        // Get the string version of this key.
        $string_key = Type::getStringKey($offset);

        // Store the key-value pair in the items array.
        $this->_items[$string_key] = new KeyValuePair($offset, $value);
    }

    /**
     * Get the value of an item by key.
     *
     * @param mixed $offset The key to get.
     * @return mixed The value of the item.
     */
    public function offsetGet(mixed $offset): mixed
    {
        // Get the string version of this key.
        $string_key = Type::getStringKey($offset);

        // Check key exists.
        if (!key_exists($string_key, $this->_items)) {
            throw new OutOfBoundsException("Unknown key.");
        }

        // Get the corresponding value.
        return $this->_items[$string_key]->value;
    }

    /**
     * Check if an item exists by key.
     *
     * @param mixed $offset The key to check.
     * @return bool True if the item exists, false otherwise.
     */
    public function offsetExists(mixed $offset): bool
    {
        // Get the string version of this key.
        $string_key = Type::getStringKey($offset);

        // Check key exists.
        return key_exists($string_key, $this->_items);
    }

    /**
     * Unset an item by key.
     *
     * @param mixed $offset The key to unset.
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        // Get the string version of this key.
        $string_key = Type::getStringKey($offset);

        // Check key exists.
        if (!key_exists($string_key, $this->_items)) {
            throw new OutOfBoundsException("Unknown key.");
        }

        // Unset the array item.
        unset($this->_items[$string_key]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Countable implementation

    /**
     * Get the number of items in the array.
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
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        foreach ($this->_items as $item) {
            yield $item->key => $item->value;
        }
    }
}
