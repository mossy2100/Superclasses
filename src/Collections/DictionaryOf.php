<?php

declare(strict_types = 1);

namespace Superclasses\Collections;

use OutOfBoundsException;
use Override;
use DomainException;
use Superclasses\Types\TypeSet;

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
    public private(set) TypeSet $keyTypes;

    /**
     * SetOf of valid types for values.
     *
     * @var TypeSet
     */
    public private(set) TypeSet $valueTypes;

    /**
     * Constructor.
     *
     * @param string|iterable $key_types   Allowed types for dictionary keys. Accepts a type string
     *                                     (e.g. 'int|string|null') or an iterable of type names.
     * @param string|iterable $value_types Allowed types for dictionary values. Accepts a type string
     *                                     (e.g. 'float|bool') or an iterable of type names.
     */
    public function __construct(string|iterable $key_types = 'mixed', string|iterable $value_types = 'mixed')
    {
        // Call the parent constructor.
        parent::__construct();

        // Convert provided types to TypeSet objects.
        $this->keyTypes = TypeSet::toTypeSet($key_types);
        $this->valueTypes = TypeSet::toTypeSet($value_types);
    }

    /**
     * Construct a new DictionaryOf from an existing collection.
     *
     * The key and value types will be inferred from the collection's items.
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

        // At this point, keyTypes and valueTypes have been initialized with a default value of 'mixed'.
        // Let's get rid of that.
        $dict->keyTypes->clear();
        $dict->valueTypes->clear();

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
            throw new DomainException("Invalid key type.");
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
            throw new DomainException("Invalid value type.");
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
