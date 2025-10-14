<?php

declare(strict_types = 1);

namespace Superclasses\Math;

use ArrayAccess;
use DomainException;
use InvalidArgumentException;

final class Vector implements ArrayAccess
{
    // region Properties

    /**
     * The vector data.
     *
     * @var array
     */
    private array $data;

    /**
     * The magnitude (norm) of the vector.
     *
     * @var float
     */
    public float $mag {
        get => sqrt(array_sum(array_map(fn($x) => $x * $x, $this->data)));
    }

    /**
     * The number of elements in the vector.
     *
     * @var int
     */
    public int $size {
        get => count($this->data);
    }

    // endregion

    // region Constructor

    /**
     * Create a new vector with the specified size.
     *
     * @param int $size Number of elements
     * @throws InvalidArgumentException If size is not positive.
     */
    public function __construct(int $size)
    {
        if ($size <= 0) {
            throw new InvalidArgumentException("Vector size must be a positive integer.");
        }

        $this->data = array_fill(0, $size, 0);
    }

    // endregion

    // region Factory methods

    /**
     * Create a vector from an array.
     *
     * @param array $data Array of numbers
     * @return self
     * @throws InvalidArgumentException If data is invalid
     */
    public static function fromArray(array $data): self
    {
        // Check if data is empty or not a list.
        if (empty($data) || !array_is_list($data)) {
            throw new InvalidArgumentException("Vector data must be a non-empty list (array with sequential indices).");
        }

        // Check if all elements are numbers.
        foreach ($data as $value) {
            if (!Numbers::isNumber($value)) {
                throw new InvalidArgumentException("Vector elements must be numbers (int or float).");
            }
        }

        // Create the vector.
        $vector = new self(count($data));
        $vector->data = array_values($data);

        return $vector;
    }

    // endregion

    // region ArrayAccess implementation

    /**
     * Check if an offset exists.
     *
     * @param mixed $offset Index to check
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return is_int($offset) && $offset >= 0 && $offset < $this->size;
    }

    /**
     * Get value at an offset.
     *
     * @param mixed $offset Index to get
     * @return int|float
     * @throws InvalidArgumentException If offset is invalid
     */
    public function offsetGet(mixed $offset): int|float
    {
        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException("Vector index out of bounds.");
        }

        return $this->data[$offset];
    }

    /**
     * Set value at an offset.
     *
     * @param mixed $offset Index to set
     * @param mixed $value Value to set
     * @throws InvalidArgumentException If offset or value is invalid
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!$this->offsetExists($offset)) {
            throw new InvalidArgumentException("Vector index out of bounds.");
        }

        if (!Numbers::isNumber($value)) {
            throw new InvalidArgumentException("Vector elements must be numbers (int or float).");
        }

        $this->data[$offset] = $value;
    }

    /**
     * Unset is not supported for vectors.
     *
     * @param mixed $offset
     * @throws DomainException Always throws
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new DomainException("Cannot unset elements in a vector.");
    }

    // endregion

    // region Vector operations

    /**
     * Add another vector to this one.
     *
     * @param self $other Vector to add
     * @return self New vector representing the sum
     * @throws InvalidArgumentException If vectors have different sizes
     */
    public function add(self $other): self
    {
        if ($this->size !== $other->size) {
            throw new InvalidArgumentException("Vectors must have the same size for addition.");
        }

        $result = new self($this->size);
        for ($i = 0; $i < $this->size; $i++) {
            $result->data[$i] = $this->data[$i] + $other->data[$i];
        }

        return $result;
    }

    /**
     * Subtract another vector from this one.
     *
     * @param self $other Vector to subtract
     * @return self New vector representing the difference
     * @throws InvalidArgumentException If vectors have different sizes
     */
    public function sub(self $other): self
    {
        if ($this->size !== $other->size) {
            throw new InvalidArgumentException("Vectors must have the same size for subtraction.");
        }

        $result = new self($this->size);
        for ($i = 0; $i < $this->size; $i++) {
            $result->data[$i] = $this->data[$i] - $other->data[$i];
        }

        return $result;
    }

    /**
     * Multiply this vector by a scalar.
     *
     * @param int|float $scalar Number to multiply by
     * @return self New vector representing the product
     */
    public function mul(int|float $scalar): self
    {
        $result = new self($this->size);
        for ($i = 0; $i < $this->size; $i++) {
            $result->data[$i] = $this->data[$i] * $scalar;
        }

        return $result;
    }

    /**
     * Divide this vector by a scalar.
     *
     * @param int|float $scalar Number to divide by
     * @return self New vector representing the quotient
     */
    public function div(int|float $scalar): self
    {
        return $this->mul(1.0 / $scalar);
    }

    /**
     * Calculate the dot product of this vector with another vector.
     *
     * @param self $other Vector to calculate dot product with
     * @return float The dot product
     * @throws InvalidArgumentException If vectors have different sizes
     */
    public function dot(self $other): float
    {
        // Check if vectors have the same size.
        if ($this->size !== $other->size) {
            throw new InvalidArgumentException("Vectors must have the same size for dot product.");
        }

        $result = 0.0;
        for ($i = 0; $i < $this->size; $i++) {
            $result += $this->data[$i] * $other->data[$i];
        }

        return $result;
    }

    /**
     * Calculate the cross product of this vector with another vector (both must be size 3).
     *
     * @param self $other Vector to calculate cross product with.
     * @return self New vector representing the cross product.
     * @throws DomainException If vectors are not size 3.
     */
    public function cross(self $other): self
    {
        // Check if vectors are size 3.
        if ($this->size !== 3) {
            throw new DomainException("First operand must be a vector of size 3.");
        }
        if ($other->size !== 3) {
            throw new DomainException("Second operand must be a vector of size 3.");
        }

        return self::fromArray([
            $this->data[1] * $other->data[2] - $this->data[2] * $other->data[1],
            $this->data[2] * $other->data[0] - $this->data[0] * $other->data[2],
            $this->data[0] * $other->data[1] - $this->data[1] * $other->data[0]
        ]);
    }

    // endregion

    // region Comparison methods

    /**
     * Check if this vector equals another.
     *
     * @param self $other The vector to compare with.
     * @param float $epsilon The tolerance for floating-point comparison.
     * @return bool True if the numbers are equal within the tolerance.
     */
    public function eq(self $other, float $epsilon = 1E-10): bool
    {
        // Check if vectors have the same size.
        if ($this->size !== $other->size) {
            throw new InvalidArgumentException("Vectors must have the same size for dot product.");
        }

        $equal = true;
        for ($i = 0; $i < $this->size; $i++) {
            $equal &= abs($this->data[$i] - $other->data[$i]) < $epsilon;
        }
        return $equal;
    }

    // endregion

    // region Conversion methods

    /**
     * Get a copy of the vector data as an array.
     *
     * @return array Array of vector elements
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Convert the vector to a string representation.
     *
     * @return string String representation
     */
    public function __toString(): string
    {
        return '[' . implode(', ', $this->data) . ']';
    }

    // endregion
}
