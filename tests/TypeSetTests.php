<?php

declare(strict_types=1);

namespace Superclasses;

use ArrayObject;
use DateTime;
use Countable;

require_once __DIR__ . '/../src/TypeSet.php';

////////////////////////////////////////////////////////////////////////////////////////////////////

$typeSet = new TypeSet('string|Serializable|TraitA');

// Primitive type
echo $typeSet->match('hello') . PHP_EOL;           // true

// Interface
echo $typeSet->match(new ArrayObject()) . PHP_EOL; // true (implements Serializable)

// Trait usage
trait TraitA
{
    public function sayHello()
    {
        echo 'Hello';
    }
}

class MyClass
{
    use TraitA;
}
echo $typeSet->match(new MyClass()) . PHP_EOL;     // true (uses TraitA)

// Complex scenario
$typeSet = new TypeSet('iterable|Countable');
echo $typeSet->match([1, 2, 3]) . PHP_EOL;         // true (array is iterable)
echo $typeSet->match(new ArrayObject()) . PHP_EOL; // true (implements Countable)

$x = new class () extends DateTime implements Countable {
    public array $items;
    public function count(): int
    {
        return count($this->items);
    }
};

echo TypeSet::getType($x);
