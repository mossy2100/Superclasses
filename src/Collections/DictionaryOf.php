<?php

declare(strict_types = 1);

namespace Superclasses\Collections;

use DomainException;
use OutOfBoundsException;
use Override;

/**
 * Dictionary class that restricts keys and values to certain types.
 *
 * @example
 * $customers = new DictionaryOf('int', 'Customer');
 * $sales_data = new DictionaryOf('DateTime', 'float');
 * $country_codes = new DictionaryOf('string', 'string');
 * $car_make = new DictionaryOf('string', '?string');
 */
class DictionaryOf extends Dictionary
{
    /**
     * SetOf of valid types for keys.
     *
     * @var TypeSet
     */
    private(set) TypeSet $keyTypes;

    /**
     * SetOf of valid types for values.
     *
     * @var TypeSet
     */
    private(set) TypeSet $valueTypes;

    /**
     * Constructor.
     *
     * @param string|iterable|null $key_types   Allowed types for dictionary keys. Accepts a type string
     *                                     (e.g. 'int|string|null'), or an iterable of type names, or null for any.
     * @param string|iterable|null $value_types Allowed types for dictionary values. Accepts a type string
     *                                     (e.g. 'float|bool'), or an iterable of type names, or null for any.
     */
    public function __construct(string|iterable|null $key_types = null, string|iterable|null $value_types = null)
    {
        // Call the parent constructor.
        parent::__construct();

        // Convert provided types to TypeSet objects as needed.
        $this->keyTypes = $key_types === null ? null : TypeSet::toTypeSet($key_types);
        $this->valueTypes = $value_types === null ? null : TypeSet::toTypeSet($value_types);
    }

    /**
     * Construct a new DictionaryOf from an existing collection.
     *
     * The key and value types will be inferred from the collection's items.
     *
     * TODO Update to allow specifying 'auto' to collect key and value types from the iterable, null to permit any,
     * or even a specific type set, as in the constructor.
     *
     * @param iterable $src The source collection.
     * @return static The new dictionary.
     */
    #[Override]
    public static function fromIterable(iterable $src): static
    {
        // If the source collection is empty, return an empty dictionary.
        if (empty($src)) {
            return new static();
        }

        // Populate the dictionary from the source collection without worrying about types.
        $dict = parent::fromIterable($src);

        // Collect the key and value types from the source collection.
        foreach ($src as $key => $value) {
            $dict->keyTypes->addValueType($key);
            $dict->valueTypes->addValueType($value);
        }

        return $dict;
    }

    /**
     * Checks if the given key matches the allowed key types.
     *
     * @param mixed $key The key to check.
     * @throws DomainException If the key type is not allowed.
     */
    private function checkKeyType(mixed $key): void
    {
        if (!$this->keyTypes->match($key)) {
            throw new DomainException("Disallowed key type.");
        }
    }

    /**
     * Checks if the given value matches the allowed value types.
     *
     * @param mixed $value The value to check.
     * @throws DomainException If the value type is not allowed.
     */
    private function checkValueType(mixed $value): void
    {
        if (!$this->valueTypes->match($value)) {
            throw new DomainException("Disallowed value type.");
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // ArrayAccess implementation

    /**
     * Set an item by key.
     *
     * If the key is in use, the corresponding key-value pair will be replaced.
     * If not, a new key-value pair will be added to the dictionary.
     *
     * Both the key and value types will be checked, and if either are invalid, an exception will be thrown.
     *
     * @param mixed $offset The key to set.
     * @param mixed $value The value to set.
     *
     * @throws OutOfBoundsException If no key or a null key is specified.
     * @throws DomainException If the key or value has an invalid type.
     */
    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Check the types are valid.
        $this->checkKeyType($offset);
        $this->checkValueType($value);

        // Call the parent implementation.
        parent::offsetSet($offset, $value);
    }
}
