<?php

declare(strict_types = 1);

namespace Superclasses\Tests\Math;

use Superclasses\Math\Complex;

// Example usage:
$z1 = new Complex(3, 4);     // 3 + 4i
$z2 = new Complex(1, -2);    // 1 - 2i

echo "z1 = " . $z1 . "\n";   // 3 + 4i
echo "z2 = " . $z2 . "\n";   // 1 - 2i

// Basic operations
$sum = $z1->add($z2);
echo "z1 + z2 = " . $sum . "\n";        // 4 + 2i

$diff = $z1->sub($z2);
echo "z1 - z2 = " . $diff . "\n";       // 2 + 6i

$product = $z1->mul($z2);
echo "z1 * z2 = " . $product . "\n";    // 11 + 2i

$quotient = $z1->div($z2);
echo "z1 / z2 = " . $quotient . "\n";   // -1 + 2i

// Square roots
$z3 = new Complex(4, 0);     // 4 + 0i (real number 4)
$squareRoots = $z3->roots(2);
echo "Square roots of 4:\n";
foreach ($squareRoots as $root) {
    echo "  " . $root . "\n";  // 2, -2
}

// Cube roots of unity
$unity = new Complex(1, 0);
$cubeRoots = $unity->roots(3);
echo "Cube roots of 1:\n";
foreach ($cubeRoots as $root) {
    echo "  " . $root . "\n";
}
