<?php

declare(strict_types=1);

namespace Superclasses;

use stdClass;
use DateTime;
use Stringable;

require_once __DIR__ . '/../src/Stringify.php';
require_once __DIR__ . '/../src/TypeSet.php';
require_once __DIR__ . '/../src/Dictionary.php';

echo Stringify::encode(null) . "\n";
echo Stringify::encode(true) . "\n";
echo Stringify::encode(false) . "\n";
echo Stringify::encode(123) . "\n";
echo Stringify::encode(123.45) . "\n";
echo Stringify::encode(123e45) . "\n";
echo Stringify::encode((float)123) . "\n";
echo Stringify::encode("My dog has fleas") . "\n";
echo Stringify::encode("\"Hey, you!\" she said.") . "\n";
echo Stringify::encode("'Hey, you!' she said.") . "\n";
echo Stringify::encode([1, true, "cats"]) . "\n";
echo Stringify::encode(['name' => 'Carl', 'age' => 45]) . "\n";
echo Stringify::encode([3 => 'Shaun', 12 => 'Angela', 'siblings' => [
    'Shaun' => ['sex' => 'M', 'age' => 53],
    'Alex' => ['sex' => 'M', 'age' => 51],
]]) . "\n";
echo Stringify::encode(new stdClass()) . "\n";
echo Stringify::encode(new class () {
    public int $count = 45;
    public string $name = "Bob";
    private int $age = 100;
}) . "\n";
echo Stringify::encode(new class () extends Set {
    public Set $types;
}) . "\n";
echo Stringify::encode(new DateTime()) . "\n";
echo Stringify::encode(new Dictionary('string', 'int')) . "\n";

class Cat
{
    public string $breed = "Manx";
    public string $name = "Supercat";
    private string $color = "tabby";
    protected DateTime $dateOfBirth;
    public static int $count = 0;
    public function __construct()
    {
        $this->dateOfBirth = new DateTime('2025-08-07');
        self::$count++;
    }
}
echo Stringify::encode(new Cat()) . "\n";
