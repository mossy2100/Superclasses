<?php

declare(strict_types = 1);

namespace Superclasses\Math;

use DomainException;
use InvalidArgumentException;

class Matrix
{
    private array $data;

    private int $rows {
        get {
            return $this->rows;
        }
    }

    private int $cols {
        get {
            return $this->cols;
        }
    }

    /**
     * Create a new matrix with the specified dimensions.
     *
     * @param int $rows Number of rows
     * @param int $cols Number of columns
     * @throws InvalidArgumentException If dimensions are not positive
     */
    public function __construct(int $rows, int $cols)
    {
        if ($rows <= 0 || $cols <= 0) {
            throw new InvalidArgumentException("Matrix dimensions must be positive integers");
        }

        $this->rows = $rows;
        $this->cols = $cols;
        $this->data = array_fill(0, $rows, array_fill(0, $cols, 0.0));
    }

    /**
     * Create a matrix from an array of arrays.
     *
     * @param array $data Array of arrays containing numbers
     * @return Matrix
     * @throws InvalidArgumentException If data is invalid or not rectangular
     */
    public static function fromArray(array $data): Matrix
    {
        if (empty($data)) {
            throw new InvalidArgumentException("Matrix data cannot be empty");
        }

        $rows = count($data);
        $cols = null;

        // Validate data and ensure rectangular matrix
        foreach ($data as $row) {
            if (!is_array($row) || empty($row)) {
                throw new InvalidArgumentException("Each row must be a non-empty array");
            }

            if ($cols === null) {
                $cols = count($row);
            } elseif (count($row) !== $cols) {
                throw new InvalidArgumentException("All rows must have the same number of columns");
            }

            foreach ($row as $value) {
                if (!is_int($value) && !is_float($value)) {
                    throw new InvalidArgumentException("Matrix elements must be numbers (int or float)");
                }
            }
        }

        $matrix = new Matrix($rows, $cols);
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                $matrix->data[$i][$j] = (float)$data[$i][$j];
            }
        }

        return $matrix;
    }

    /**
     * Get a matrix element.
     *
     * @param int $row Row index (0-based)
     * @param int $col Column index (0-based)
     * @return float
     * @throws InvalidArgumentException If indices are out of bounds
     */
    public function get(int $row, int $col): float
    {
        if ($row < 0 || $row >= $this->rows || $col < 0 || $col >= $this->cols) {
            throw new InvalidArgumentException("Matrix indices out of bounds");
        }
        return $this->data[$row][$col];
    }

    /**
     * Set a matrix element.
     *
     * @param int $row Row index (0-based)
     * @param int $col Column index (0-based)
     * @param int|float $value Value to set
     * @throws InvalidArgumentException If indices are out of bounds or value is not numeric
     */
    public function set(int $row, int $col, int|float $value): void
    {
        if ($row < 0 || $row >= $this->rows || $col < 0 || $col >= $this->cols) {
            throw new InvalidArgumentException("Matrix indices out of bounds");
        }
        if (!is_int($value) && !is_float($value)) {
            throw new InvalidArgumentException("Value must be a number");
        }
        $this->data[$row][$col] = (float)$value;
    }

    /**
     * Add another matrix to this one.
     *
     * @param Matrix $other Matrix to add
     * @return Matrix New matrix representing the sum
     * @throws InvalidArgumentException If matrices have different dimensions
     */
    public function add(Matrix $other): Matrix
    {
        if ($this->rows !== $other->rows || $this->cols !== $other->cols) {
            throw new InvalidArgumentException("Matrices must have the same dimensions for addition");
        }

        $result = new Matrix($this->rows, $this->cols);
        for ($i = 0; $i < $this->rows; $i++) {
            for ($j = 0; $j < $this->cols; $j++) {
                $result->data[$i][$j] = $this->data[$i][$j] + $other->data[$i][$j];
            }
        }
        return $result;
    }

    /**
     * Subtract another matrix from this one.
     *
     * @param Matrix $other Matrix to subtract
     * @return Matrix New matrix representing the difference
     * @throws InvalidArgumentException If matrices have different dimensions
     */
    public function subtract(Matrix $other): Matrix
    {
        if ($this->rows !== $other->rows || $this->cols !== $other->cols) {
            throw new InvalidArgumentException("Matrices must have the same dimensions for subtraction");
        }

        $result = new Matrix($this->rows, $this->cols);
        for ($i = 0; $i < $this->rows; $i++) {
            for ($j = 0; $j < $this->cols; $j++) {
                $result->data[$i][$j] = $this->data[$i][$j] - $other->data[$i][$j];
            }
        }
        return $result;
    }

    /**
     * Multiply this matrix by another matrix.
     *
     * @param Matrix $other Matrix to multiply by
     * @return Matrix New matrix representing the product
     * @throws InvalidArgumentException If matrices cannot be multiplied
     */
    public function multiply(Matrix $other): Matrix
    {
        if ($this->cols !== $other->rows) {
            throw new InvalidArgumentException("Matrix A columns must equal Matrix B rows for multiplication");
        }

        $result = new Matrix($this->rows, $other->cols);
        for ($i = 0; $i < $this->rows; $i++) {
            for ($j = 0; $j < $other->cols; $j++) {
                $sum = 0.0;
                for ($k = 0; $k < $this->cols; $k++) {
                    $sum += $this->data[$i][$k] * $other->data[$k][$j];
                }
                $result->data[$i][$j] = $sum;
            }
        }
        return $result;
    }

    /**
     * Divide this matrix by another matrix (A * B^-1).
     *
     * @param Matrix $other Matrix to divide by
     * @return Matrix New matrix representing the quotient
     * @throws InvalidArgumentException If division is not possible
     */
    public function divide(Matrix $other): Matrix
    {
        return $this->multiply($other->inverse());
    }

    /**
     * Get the transpose of this matrix.
     *
     * @return Matrix New matrix representing the transpose
     */
    public function transpose(): Matrix
    {
        $result = new Matrix($this->cols, $this->rows);
        for ($i = 0; $i < $this->rows; $i++) {
            for ($j = 0; $j < $this->cols; $j++) {
                $result->data[$j][$i] = $this->data[$i][$j];
            }
        }
        return $result;
    }

    /**
     * Calculate the determinant of this matrix.
     *
     * @return float The determinant
     * @throws DomainException If matrix is not square
     */
    public function determinant(): float
    {
        if ($this->rows !== $this->cols) {
            throw new DomainException("Determinant can only be calculated for square matrices");
        }

        return $this->calculateDeterminant($this->data);
    }

    /**
     * Recursive helper method to calculate determinant.
     *
     * @param array $matrix Matrix data
     * @return float
     */
    private function calculateDeterminant(array $matrix): float
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

            $cofactor = ($j % 2 === 0 ? 1 : -1) * $matrix[0][$j] * $this->calculateDeterminant($submatrix);
            $det += $cofactor;
        }

        return $det;
    }

    /**
     * Calculate the inverse of this matrix.
     *
     * @return Matrix New matrix representing the inverse
     * @throws DomainException If matrix is not square or not invertible
     */
    public function inverse(): Matrix
    {
        if ($this->rows !== $this->cols) {
            throw new DomainException("Inverse can only be calculated for square matrices");
        }

        $det = $this->determinant();
        if (abs($det) < 1e-10) {
            throw new DomainException("Matrix is not invertible (determinant is zero)");
        }

        $n = $this->rows;
        $adjugate = new Matrix($n, $n);

        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $minor = $this->getMinor($i, $j);
                $cofactor = (($i + $j) % 2 === 0 ? 1 : -1) * $this->calculateDeterminant($minor);
                $adjugate->data[$j][$i] = $cofactor / $det; // Note: transposed
            }
        }

        return $adjugate;
    }

    /**
     * Get the minor matrix by removing specified row and column.
     *
     * @param int $excludeRow Row to exclude
     * @param int $excludeCol Column to exclude
     * @return array
     */
    private function getMinor(int $excludeRow, int $excludeCol): array
    {
        $minor = [];
        for ($i = 0; $i < $this->rows; $i++) {
            if ($i !== $excludeRow) {
                $row = [];
                for ($j = 0; $j < $this->cols; $j++) {
                    if ($j !== $excludeCol) {
                        $row[] = $this->data[$i][$j];
                    }
                }
                $minor[] = $row;
            }
        }
        return $minor;
    }

    /**
     * Raise this matrix to a power.
     *
     * @param int $power Power to raise to
     * @return Matrix New matrix representing the result
     * @throws DomainException If matrix is not square
     * @throws InvalidArgumentException If power is negative and matrix is not invertible
     */
    public function pow(int $power): Matrix
    {
        if ($this->rows !== $this->cols) {
            throw new DomainException("Power can only be calculated for square matrices");
        }

        if ($power === 0) {
            return $this->identity($this->rows);
        }

        if ($power < 0) {
            return $this->inverse()->pow(-$power);
        }

        $result = $this->identity($this->rows);
        $base = clone $this;

        while ($power > 0) {
            if ($power % 2 === 1) {
                $result = $result->multiply($base);
            }
            $base = $base->multiply($base);
            $power = intval($power / 2);
        }

        return $result;
    }

    /**
     * Create an identity matrix of the specified size.
     *
     * @param int $size Size of the identity matrix
     * @return Matrix Identity matrix
     */
    public function identity(int $size): Matrix
    {
        $result = new Matrix($size, $size);
        for ($i = 0; $i < $size; $i++) {
            $result->data[$i][$i] = 1.0;
        }
        return $result;
    }

    /**
     * Calculate the dot product with another matrix (treated as vectors).
     *
     * @param Matrix $other Matrix to calculate dot product with
     * @return float The dot product
     * @throws InvalidArgumentException If matrices cannot be treated as vectors of same length
     */
    public function dot(Matrix $other): float
    {
        $thisVector = $this->toVector();
        $otherVector = $other->toVector();

        if (count($thisVector) !== count($otherVector)) {
            throw new InvalidArgumentException("Vectors must have the same length for dot product");
        }

        $result = 0.0;
        for ($i = 0; $i < count($thisVector); $i++) {
            $result += $thisVector[$i] * $otherVector[$i];
        }

        return $result;
    }

    /**
     * Calculate the cross product with another matrix (for 3D vectors).
     *
     * @param Matrix $other Matrix to calculate cross product with
     * @return Matrix New matrix representing the cross product
     * @throws InvalidArgumentException If matrices are not 3D vectors
     */
    public function cross(Matrix $other): Matrix
    {
        $thisVector = $this->toVector();
        $otherVector = $other->toVector();

        if (count($thisVector) !== 3 || count($otherVector) !== 3) {
            throw new InvalidArgumentException("Cross product is only defined for 3D vectors");
        }

        return Matrix::fromArray([[
            $thisVector[1] * $otherVector[2] - $thisVector[2] * $otherVector[1],
            $thisVector[2] * $otherVector[0] - $thisVector[0] * $otherVector[2],
            $thisVector[0] * $otherVector[1] - $thisVector[1] * $otherVector[0]
        ]]);
    }

    /**
     * Convert matrix to a flat vector array.
     *
     * @return array
     */
    private function toVector(): array
    {
        $vector = [];
        for ($i = 0; $i < $this->rows; $i++) {
            for ($j = 0; $j < $this->cols; $j++) {
                $vector[] = $this->data[$i][$j];
            }
        }
        return $vector;
    }

    /**
     * Clone the matrix.
     *
     * @return void
     */
    public function __clone(): void
    {
        $newData = [];
        for ($i = 0; $i < $this->rows; $i++) {
            $newData[$i] = [...$this->data[$i]];
        }
        $this->data = $newData;
    }

    /**
     * Convert the matrix to a string representation.
     *
     * @return string String representation with square brackets and proper formatting
     */
    public function __toString(): string
    {
        if ($this->rows === 0 || $this->cols === 0) {
            return "[]";
        }

        // Calculate the maximum width needed for formatting
        $maxWidth = 0;
        for ($i = 0; $i < $this->rows; $i++) {
            for ($j = 0; $j < $this->cols; $j++) {
                $str = (string)$this->data[$i][$j];
                $maxWidth = max($maxWidth, strlen($str));
            }
        }

        $result = "";
        for ($i = 0; $i < $this->rows; $i++) {
            $result .= "[";
            for ($j = 0; $j < $this->cols; $j++) {
                $value = str_pad((string)$this->data[$i][$j], $maxWidth, " ", STR_PAD_LEFT);
                $result .= $value;
                if ($j < $this->cols - 1) {
                    $result .= " ";
                }
            }
            $result .= "]";
            if ($i < $this->rows - 1) {
                $result .= "\n";
            }
        }

        return $result;
    }
}
