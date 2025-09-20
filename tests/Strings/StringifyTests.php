<?php

declare(strict_types = 1);

namespace Superclasses\Tests\Strings;

use stdClass;
use DateTime;
use Superclasses\Strings\Stringify;
use Superclasses\Collections\Dictionary;
use Superclasses\Collections\Set;

echo Stringify::stringify(null) . "\n";
echo Stringify::stringify(true) . "\n";
echo Stringify::stringify(false) . "\n";
echo Stringify::stringify(123) . "\n";
echo Stringify::stringify(123.45) . "\n";
echo Stringify::stringify(123e45) . "\n";
echo Stringify::stringify((float)123) . "\n";
echo Stringify::stringify("My dog has fleas") . "\n";
echo Stringify::stringify("\"Hey, you!\" she said.") . "\n";
echo Stringify::stringify("'Hey, you!' she said.") . "\n";
echo Stringify::stringify([1, true, "cats"]) . "\n";
echo Stringify::stringify(['name' => 'Carl', 'age' => 45]) . "\n";
echo Stringify::stringify([3 => 'Shaun', 12 => 'Angela', 'siblings' => [
    'Shaun' => ['sex' => 'M', 'age' => 53],
    'Alex' => ['sex' => 'M', 'age' => 51],
]]) . "\n";
echo Stringify::stringify(new stdClass()) . "\n";
echo Stringify::stringify(new class () {
    public int $count = 45;
    public string $name = "Bob";
    private int $age = 100;
}) . "\n";
echo Stringify::stringify(new class () extends Set {
    public int $count = 45;
}) . "\n";
echo Stringify::stringify(new DateTime()) . "\n";
echo Stringify::stringify(new Dictionary('string', 'int')) . "\n";

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
echo Stringify::stringify(new Cat()) . "\n";
