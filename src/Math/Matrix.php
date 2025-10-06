<?php

declare(strict_types = 1);

namespace Superclasses\Math;

use DomainException;
use InvalidArgumentException;

final class Matrix
{
    // region Properties

    /**
     * The matrix data.
     *
     * This must be private because even if it's private(set) if they can get $this->data they could add new elements
     * (inadvertently sizing the matrix without changing rowCount/colCount or making it non-rectangular) or they
     * could set elements to non-numbers.
     *
     * @var array
     */
    private array $data;

    /**
     * The number of rows in the matrix.
     *
     * @var int
     */
    public int $rowCount {
        get => count($this->data);
    }

    /**
     * The number of columns in the matrix.
     *
     * This property relies on the fact that $this->data is a 2D array with a minimum of 1 row, which is enforced by
     * the constructor, and the data property being private.
     *
     * @var int
     */
    public int $colCount {
        get => count($this->data[0]);
    }

    /**
     * A tolerance for use in calculation of the determinant of the matrix.
     *
     * @var float
     */
    private const float EPSILON = 1e-10;

    // endregion

    // region Constructor

    /**
     * Create a new matrix with the specified dimensions.
     *
     * @param int $row_count Number of rows
     * @param int $col_count Number of columns
     * @throws InvalidArgumentException If dimensions are not positive.
     */
    public function __construct(int $row_count, int $col_count)
    {
        // Check if dimensions are positive.
        if ($row_count <= 0 || $col_count <= 0) {
            throw new InvalidArgumentException("Matrix dimensions must be positive integers.");
        }

        // Initialize matrix properties.
        $this->data = array_fill(0, $row_count, array_fill(0, $col_count, 0));
    }

    // endregion

    // region Factory methods

    /**
     * Create a matrix from a 2D array.
     *
     * @param array $data Array of arrays containing numbers.
     * @return self
     * @throws InvalidArgumentException If data is invalid or not rectangular.
     */
    public static function fromArray(array $data): self
    {
        // Check if data is empty.
        if (empty($data) || !array_is_list($data)) {
            throw new InvalidArgumentException("Matrix data must be a non-empty list (array with sequential indices).");
        }

        $row_count = count($data);
        $col_count = null;

        // Validate data and ensure rectangular matrix.
        foreach ($data as $row) {
            // Check if each row is a non-empty list.
            if (!is_array($row) || empty($row) || !array_is_list($row)) {
                throw new InvalidArgumentException("Each row must be a non-empty list (array with sequential indices).");
            }

            if ($col_count === null) {
                $col_count = count($row);
            }
            elseif (count($row) !== $col_count) {
                throw new InvalidArgumentException("All rows must have the same number of columns.");
            }

            foreach ($row as $value) {
                if (!is_int($value) && !is_float($value)) {
                    throw new InvalidArgumentException("Matrix elements must be numbers (int or float).");
                }
            }
        }

        // Create the matrix.
        $matrix = new self($row_count, $col_count);
        for ($i = 0; $i < $row_count; $i++) {
            for ($j = 0; $j < $col_count; $j++) {
                $matrix->data[$i][$j] = $data[$i][$j];
            }
        }

        return $matrix;
    }
    /**
     * Create a row vector from a 1D array.
     *
     * @param array $data 1D list of numbers
     * @return self Row vector (1×n matrix)
     * @throws InvalidArgumentException If data is invalid
     */
    public static function createRowVector(array $data): self
    {
        // Check if data is empty.
        if (empty($data) || !array_is_list($data)) {
            throw new InvalidArgumentException("Vector data must be a non-empty list (array with sequential indices).");
        }

        return self::fromArray([$data]);
    }

    /**
     * Create a column vector from a 1D array.
     *
     * @param array $data 1D array of numbers
     * @return self Column vector (n×1 matrix)
     * @throws InvalidArgumentException If data is invalid
     */
    public static function createColVector(array $data): self
    {
        // Check if data is empty.
        if (empty($data) || !array_is_list($data)) {
            throw new InvalidArgumentException("Vector data must be a non-empty list (array with sequential indices).");
        }

        $column = [];
        foreach ($data as $value) {
            $column[] = [$value];
        }

        return self::fromArray($column);
    }

    /**
     * Create an identity matrix of the specified size.
     *
     * @param int $size Size of the identity matrix
     * @return self Identity matrix
     */
    public static function identity(int $size): self
    {
        $result = new self($size, $size);
        for ($i = 0; $i < $size; $i++) {
            $result->data[$i][$i] = 1.0;
        }
        return $result;
    }

    // endregion

    // region Get/set matrix elements

    /**
     * Get a matrix element.
     *
     * @param int $row Row index (0-based).
     * @param int $col Column index (0-based).
     * @return int|float Value of the matrix element.
     * @throws InvalidArgumentException If indices are out of bounds.
     */
    public function get(int $row, int $col): int|float
    {
        // Check if indices are within bounds.
        if ($row < 0 || $row >= $this->rowCount || $col < 0 || $col >= $this->colCount) {
            throw new InvalidArgumentException("Matrix indices out of bounds.");
        }

        return $this->data[$row][$col];
    }

    /**
     * Set a matrix element.
     *
     * @param int $row Row index (0-based)
     * @param int $col Column index (0-based)
     * @param int|float $value Value to set.
     * @throws InvalidArgumentException If indices are out of bounds.
     */
    public function set(int $row, int $col, int|float $value): void
    {
        // Check if indices are within bounds.
        if ($row < 0 || $row >= $this->rowCount || $col < 0 || $col >= $this->colCount) {
            throw new InvalidArgumentException("Matrix indices out of bounds.");
        }

        $this->data[$row][$col] = $value;
    }
    /**
     * Get a row as a row vector.
     *
     * @param int $row Row index (0-based)
     * @return self Row vector
     * @throws InvalidArgumentException If row index is out of bounds
     */
    public function getRow(int $row): self
    {
        // Check if row index is within bounds.
        if ($row < 0 || $row >= $this->rowCount) {
            throw new InvalidArgumentException("Row index out of bounds.");
        }

        return self::fromArray([$this->data[$row]]);
    }

    /**
     * Get a column as a column vector.
     *
     * @param int $col Column index (0-based)
     * @return self Column vector
     * @throws InvalidArgumentException If column index is out of bounds
     */
    public function getCol(int $col): self
    {
        // Check if column index is within bounds.
        if ($col < 0 || $col >= $this->colCount) {
            throw new InvalidArgumentException("Column index out of bounds.");
        }

        $column = [];
        for ($i = 0; $i < $this->rowCount; $i++) {
            $column[] = [$this->data[$i][$col]];
        }

        return self::fromArray($column);
    }
    // endregion

    // region Inspection methods

    /**
     * Check if the matrix is square, optionally of a specific size.
     *
     * @param int|null $size If specified, check for exact size, otherwise any size.
     * @return bool True if square, false otherwise.
     */
    public function isSquare(?int $size = null): bool {
        return ($this->rowCount === $this->colCount) && ($size === null || $this->rowCount === $size);
    }

    /**
     * Check if the matrix is a row vector.
     *
     * @param int|null $size If specified, check for exact size, otherwise any size.
     * @return bool True if row vector (optionally of specified size).
     */
    public function isRowVector(?int $size = null): bool
    {
        // Check if there's only one row.
        if ($this->rowCount !== 1) {
            return false;
        }

        // Check if the row is the correct size.
        return $size === null || $this->colCount === $size;
    }

    /**
     * Check if the matrix is a column vector.
     *
     * @param int|null $size If specified, check for exact size, otherwise any size.
     * @return bool True if column vector (optionally of specified size).
     */
    public function isColVector(?int $size = null): bool
    {
        // Check if there's only one column.
        if ($this->colCount !== 1) {
            return false;
        }

        // Check if the column is the correct size.
        return $size === null || $this->rowCount === $size;
    }

    /**
     * Check if the matrix is a vector (row or column).
     *
     * @param int|null $size If specified, check for exact size, otherwise any size.
     * @return bool True if vector (optionally of specified size).
     */
    public function isVector(?int $size = null): bool
    {
        return $this->isRowVector($size) || $this->isColVector($size);
    }

    // endregion

    // region Matrix operations

    /**
     * Add another matrix to this one.
     *
     * @param self $other Matrix to add
     * @return self New matrix representing the sum
     * @throws InvalidArgumentException If matrices have different dimensions.
     */
    public function add(self $other): self
    {
        // Check if dimensions are the same.
        if ($this->rowCount !== $other->rowCount || $this->colCount !== $other->colCount) {
            throw new InvalidArgumentException("Matrices must have the same dimensions for addition.");
        }

        // Add the matrices.
        $result = new self($this->rowCount, $this->colCount);
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->colCount; $j++) {
                $result->data[$i][$j] = $this->data[$i][$j] + $other->data[$i][$j];
            }
        }
        return $result;
    }

    /**
     * Subtract another matrix from this one.
     *
     * @param self $other Matrix to subtract.
     * @return self New matrix representing the difference.
     * @throws InvalidArgumentException If matrices have different dimensions.
     */
    public function sub(self $other): self
    {
        // Check if dimensions are the same.
        if ($this->rowCount !== $other->rowCount || $this->colCount !== $other->colCount) {
            throw new InvalidArgumentException("Matrices must have the same dimensions for subtraction");
        }

        // Subtract the matrices.
        $result = new self($this->rowCount, $this->colCount);
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->colCount; $j++) {
                $result->data[$i][$j] = $this->data[$i][$j] - $other->data[$i][$j];
            }
        }
        return $result;
    }

    /**
     * Calculate the inverse of this matrix.
     *
     * @return self New matrix representing the inverse.
     * @throws DomainException If matrix is not square or not invertible.
     */
    public function inv(): self
    {
        // Check if matrix is square.
        if (!$this->isSquare()) {
            throw new DomainException("Inverse can only be calculated for square matrices.");
        }

        // Use Gauss-Jordan elimination to calculate the inverse.
        $det = $this->det();
        if (abs($det) < self::EPSILON) {
            throw new DomainException("Matrix is not invertible (determinant is zero).");
        }

        $n = $this->rowCount;
        $adjugate = new self($n, $n);

        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $minor = $this->getMinor($i, $j);
                $cofactor = (($i + $j) % 2 === 0 ? 1 : -1) * $this->calcDet($minor);
                $adjugate->data[$j][$i] = $cofactor / $det; // Note: transposed
            }
        }

        return $adjugate;
    }

    /**
     * Multiply this matrix by a scalar or another matrix.
     *
     * @param int|float|self $other Number or matrix to multiply by.
     * @return self New matrix representing the product.
     * @throws InvalidArgumentException If operands cannot be multiplied.
     */
    public function mul(int|float|self $other): self
    {
        // Check if operand is a number.
        if (Numbers::isNumber($other)) {
            $scaled = new self($this->rowCount, $this->colCount);
            for ($i = 0; $i < $this->rowCount; $i++) {
                for ($j = 0; $j < $this->colCount; $j++) {
                    $scaled->data[$i][$j] = $this->data[$i][$j] * $other;
                }
            }
            return $scaled;
        }

        // Check if dimensions are compatible for multiplication.
        if ($this->colCount !== $other->rowCount) {
            throw new InvalidArgumentException("Matrix A columns must equal Matrix B rows for multiplication");
        }

        // Multiply the matrices.
        $result = new self($this->rowCount, $other->colCount);
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $other->colCount; $j++) {
                $sum = 0.0;
                for ($k = 0; $k < $this->colCount; $k++) {
                    $sum += $this->data[$i][$k] * $other->data[$k][$j];
                }
                $result->data[$i][$j] = $sum;
            }
        }

        return $result;
    }

    /**
     * Divide this matrix by a number or another matrix (A * B^-1).
     *
     * @param int|float|self $other Number or matrix to divide by.
     * @return self New matrix representing the quotient.
     * @throws InvalidArgumentException If division is not possible.
     */
    public function div(int|float|self $other): self
    {
        $m = Numbers::isNumber($other) ? 1.0 / $other : $other->inv();
        return $this->mul($m);
    }

    /**
     * Raise this matrix to a power.
     *
     * @param int $power Power to raise to
     * @return self New matrix representing the result
     * @throws DomainException If matrix is not square
     * @throws InvalidArgumentException If power is negative and matrix is not invertible
     */
    public function pow(int $power): self
    {
        // Check if matrix is square.
        if (!$this->isSquare()) {
            throw new DomainException("Power can only be calculated for square matrices.");
        }

        // Handle zero power.
        if ($power === 0) {
            return self::identity($this->rowCount);
        }

        // Handle negative powers.
        if ($power < 0) {
            return $this->inv()->pow(-$power);
        }

        $result = $this->identity($this->rowCount);
        $base = clone $this;

        while ($power > 0) {
            if ($power % 2 === 1) {
                $result = $result->mul($base);
            }
            $base = $base->mul($base);
            $power = intval($power / 2);
        }

        return $result;
    }

    /**
     * Get the transpose of this matrix.
     *
     * @return self New matrix representing the transpose.
     */
    public function T(): self
    {
        $result = new self($this->colCount, $this->rowCount);
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->colCount; $j++) {
                $result->data[$j][$i] = $this->data[$i][$j];
            }
        }
        return $result;
    }

    /**
     * Calculate the determinant of this matrix.
     *
     * @return float The determinant.
     * @throws DomainException If matrix is not square.
     */
    public function det(): float
    {
        // Check if matrix is square.
        if (!$this->isSquare()) {
            throw new DomainException("Determinant can only be calculated for square matrices.");
        }

        return $this->calcDet($this->data);
    }

    /**
     * Calculate the dot product of this matrix with another matrix. Operands must be column vectors of equal size.
     *
     * @param self $other Vector to calculate dot product with.
     * @return float The dot product.
     * @throws InvalidArgumentException If matrices are not column vectors of equal size.
     */
    public function dot(self $other): float
    {
        // Check if both are column vectors and have the same size.
        if (!$this->isColVector()) {
            throw new InvalidArgumentException("First operand must be a column vector.");
        }
        if (!$other->isColVector()) {
            throw new InvalidArgumentException("Second operand must be a column vector.");
        }
        if ($this->rowCount !== $other->rowCount) {
            throw new InvalidArgumentException("Column vectors must have the same size for dot product.");
        }

        // Compute the dot product.
        $result = 0.0;
        for ($i = 0; $i < $this->rowCount; $i++) {
            $result += $this->data[$i][0] * $other->data[$i][0];
        }

        return $result;
    }
    /**
     * Calculate the cross product of this matrix with another matrix (both operands must be column vectors of size 3).
     *
     * @param self $other Vector to calculate cross product with.
     * @return self New vector representing the cross product.
     * @throws InvalidArgumentException If matrices are not column vectors of size 3.
     */
    public function cross(self $other): self
    {
        // Check if both are column vectors of length 3.
        if (!$this->isColVector(3)) {
            throw new InvalidArgumentException("First operand must be a column vector of size 3.");
        }
        if (!$other->isColVector(3)) {
            throw new InvalidArgumentException("Second operand must be a column vector of size 3.");
        }

        // Calculate cross product.
        return self::fromArray([
            [ $this->data[1][0] * $other->data[2][0] - $this->data[2][0] * $other->data[1][0] ],
            [ $this->data[2][0] * $other->data[0][0] - $this->data[0][0] * $other->data[2][0] ],
            [ $this->data[0][0] * $other->data[1][0] - $this->data[1][0] * $other->data[0][0] ]
        ]);
    }

    /**
     * Calculate the magnitude of this matrix, which must be a column vector.
     *
     * @return float The magnitude.
     * @throws InvalidArgumentException If matrix is not a column vector.
     */
    public function mag(): float {
        // Ensure matrix is a vector.
        if (!$this->isVector()) {
            throw new InvalidArgumentException("Matrix must be a vector.");
        }

        // Convert to a column vector if necessary.
        $a = $this->isColVector() ? $this : $this->T();

        // Calculate magnitude.
        return sqrt($a->dot($a));
    }

    // endregion

    // region Helper methods

    /**
     * Recursive helper method to calculate determinant.
     *
     * @param array $matrix Matrix data
     * @return float Determinant of the matrix.
     */
    private function calcDet(array $matrix): float
    {
        $n = count($matrix);

        if ($n === 1) {
            return $matrix[0][0];
        }

        if ($n === 2) {
            return $matrix[0][0] * $matrix[1][1] - $matrix[0][1] * $matrix[1][0];
        }

        $det = 0.0;
        for ($j = 0; $j < $n; $j++) {
            $submatrix = [];
            for ($i = 1; $i < $n; $i++) {
                $row = [];
                for ($k = 0; $k < $n; $k++) {
                    if ($k !== $j) {
                        $row[] = $matrix[$i][$k];
                    }
                }
                $submatrix[] = $row;
            }

            $cofactor = ($j % 2 === 0 ? 1 : -1) * $matrix[0][$j] * $this->calcDet($submatrix);
            $det += $cofactor;
        }

        return $det;
    }

    /**
     * Get the minor matrix by removing specified row and column.
     *
     * @param int $exclude_row Row to exclude
     * @param int $exclude_col Column to exclude
     * @return array
     */
    private function getMinor(int $exclude_row, int $exclude_col): array
    {
        $minor = [];
        for ($i = 0; $i < $this->rowCount; $i++) {
            if ($i !== $exclude_row) {
                $row = [];
                for ($j = 0; $j < $this->colCount; $j++) {
                    if ($j !== $exclude_col) {
                        $row[] = $this->data[$i][$j];
                    }
                }
                $minor[] = $row;
            }
        }
        return $minor;
    }

    // endregion

    // region Conversion methods

    /**
     * Get a copy of the matrix data as a 2D array.
     *
     * @return array 2D array of matrix elements
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Convert the matrix to a string representation.
     *
     * @return string String representation with square brackets and neat formatting.
     */
    public function __toString(): string
    {
        if ($this->rowCount === 0 || $this->colCount === 0) {
            return "[]";
        }

        // Calculate the maximum width needed for formatting
        $maxWidth = 0;
        for ($i = 0; $i < $this->rowCount; $i++) {
            for ($j = 0; $j < $this->colCount; $j++) {
                $str = (string)$this->data[$i][$j];
                $maxWidth = max($maxWidth, strlen($str));
            }
        }

        $result = "";
        for ($i = 0; $i < $this->rowCount; $i++) {
            $result .= "[";
            for ($j = 0; $j < $this->colCount; $j++) {
                $value = str_pad((string)$this->data[$i][$j], $maxWidth, " ", STR_PAD_LEFT);
                $result .= $value;
                if ($j < $this->colCount - 1) {
                    $result .= " ";
                }
            }
            $result .= "]";
            if ($i < $this->rowCount - 1) {
                $result .= "\n";
            }
        }

        return $result;
    }

    // endregion
}
