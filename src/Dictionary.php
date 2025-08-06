<?php

declare(strict_types=1);

namespace Superclasses;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;
use InvalidArgumentException;
use OutOfBoundsException;

require_once __DIR__ . '/TypeSet.php';
require_once __DIR__ . '/KeyValuePair.php';

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
     * Set of valid types for keys.
     *
     * @var TypeSet
     */
    public private(set) TypeSet $keyTypes;

    /**
     * Set of valid types for values.
     *
     * @var TypeSet
     */
    public private(set) TypeSet $valueTypes;

    /**
     * Array of key-value pairs in the dictionary.
     *
     * @var KeyValuePair[]
     */
    public private(set) array $items = [];

    /**
     * Constructor.
     *
     * @param string|iterable $key_types   Allowed types for dictionary keys. Accepts a type string (e.g. 'int|string|null')
     *                                     or an iterable of type names.
     * @param string|iterable $value_types Allowed types for dictionary values. Accepts a type string (e.g. 'float|bool')
     *                                     or an iterable of type names.
     *
     * @throws InvalidArgumentException If only 'null' is specified for keys or values, or if types are invalid.
     */
    public function __construct(string|iterable $key_types = 'mixed', string|iterable $value_types = 'mixed')
    {
        // Convert provided types to TypeSet objects.
        $this->keyTypes = new TypeSet($key_types);
        $this->valueTypes = new TypeSet($value_types);

        // Make sure something other than null by itself is specified for both keys and values.
        if ($this->keyTypes->isNullOnly()) {
            throw new InvalidArgumentException("Key types cannot be only null.");
        }
        if ($this->valueTypes->isNullOnly()) {
            throw new InvalidArgumentException("Value types cannot be only null.");
        }
    }

    /**
     * Construct a new Dictionary from an existing collection.
     */
    public static function fromIterable(iterable $src)
    {
        // Collect the key and value types.
        $key_types = new TypeSet();
        $value_types = new TypeSet();
        foreach ($src as $key => $value) {
            $key_types->addValueType($key);
            $value_types->addValueType($value);
        }

        // Instantiate the dictionary.
        $dict = new self($key_types, $value_types);

        // Copy the values into the new dictionary.
        foreach ($src as $key => $value) {
            $dict[$key] = $value;
        }

        return $dict;
    }

    /**
     * Convert any PHP value into a unique string key.
     */
    public static function getStringKey(mixed $key)
    {
        $type = get_debug_type($key);
        switch ($type) {
            case 'null':
                return 'n';

            case 'bool':
                return 'b:' . ($key ? 'T' : 'F');

            case 'int':
                return 'i:' . $key;

            case 'float':
                return 'f:' . $key;

            case 'string':
                return 's:' . strlen($key) . ':' . $key;

            case 'array':
                $array_item_keys = array_map('Dictionary::getStringKey', $key);
                return 'a:' . count($key) . ':[' . implode(', ', $array_item_keys) . ']';
        }

        if (is_object($key)) {
            return 'o:' . spl_object_id($key);
        }

        if (str_starts_with($type, 'resource')) {
            return 'r:' . get_resource_id($key);
        }

        // Not sure if this can ever actually happen. gettype() can return 'unknown type' but
        // get_debug_type() has no equivalent.
        throw new InvalidArgumentException("Key has unknown type.");
    }

    /**
     * Checks if the given key matches the allowed key types.
     *
     * @param mixed $key The key to check.
     * @throws InvalidArgumentException If the key type is not allowed.
     */
    private function checkKeyType(mixed $key)
    {
        if (!$this->keyTypes->match($key)) {
            throw new InvalidArgumentException("Invalid key type.");
        }
    }

    /**
     * Checks if the given value matches the allowed value types.
     *
     * @param mixed $value The value to check.
     * @throws InvalidArgumentException If the value type is not allowed.
     */
    private function checkValueType(mixed $value)
    {
        if (!$this->valueTypes->match($value)) {
            throw new InvalidArgumentException("Invalid value type.");
        }
    }

    /**
     * Get all the keys as an array.
     */
    public function keys(): array
    {
        return array_column($this->items, 'key');
    }

    /**
     * Get all the values as an array.
     */
    public function values(): array
    {
        return array_column($this->items, 'value');
    }

    /**
     * Get all the key-value pairs as an array.
     */
    public function entries(): array
    {
        return array_values($this->items);
    }

    /**
     * Clear all items
     */
    public function clear(): void
    {
        $this->items = [];
    }

    /**
     * Check if dictionary is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // ArrayAccess implementation

    /**
     * Set an item by key.
     *
     * If they key is in use, the corresponding key-value pair will be replaced.
     * If not, a new key-value pair will be added to the dictionary.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @throws OutOfBoundsException If no key or a null key is specified.
     * @throws InvalidArgumentException If the key or value has an invalid type.
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        if ($key === null) {
            throw new OutOfBoundsException("A key must be specified.");
        }

        // Check the types are valid.
        $this->checkKeyType($key);
        $this->checkValueType($value);

        // Get the string version of this key.
        $string_key = self::getStringKey($key);

        // Store the key-value pair in the items array.
        $this->items[$string_key] = new KeyValuePair($key, $value);
    }

    public function offsetGet(mixed $key): mixed
    {
        // Get the string version of this key.
        $string_key = self::getStringKey($key);

        // Check key exists.
        if (!key_exists($string_key, $this->items)) {
            throw new OutOfBoundsException("Unknown key.");
        }

        // Get the corresponding value.
        return $this->items[$string_key]->value;
    }

    public function offsetExists(mixed $key): bool
    {
        // Get the string version of this key.
        $string_key = self::getStringKey($key);

        // Check key exists.
        return key_exists($string_key, $this->items);
    }

    public function offsetUnset(mixed $key): void
    {
        // Get the string version of this key.
        $string_key = self::getStringKey($key);

        // Check key exists.
        if (!key_exists($string_key, $this->items)) {
            throw new OutOfBoundsException("Unknown key.");
        }

        // Unset the array item.
        unset($this->items[$string_key]);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Countable implementation

    /**
     * Get the number of items in the array.
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
        foreach ($this->items as $item) {
            yield $item->key => $item->value;
        }
    }
}
