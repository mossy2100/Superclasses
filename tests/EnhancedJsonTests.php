<?php

declare(strict_types=1);

namespace Superclasses;

use stdClass;
use DateTime;

require_once __DIR__ . '/../src/EnhancedJson.php';
require_once __DIR__ . '/../src/TypeSet.php';
require_once __DIR__ . '/../src/Dictionary.php';

echo EnhancedJson::encode(null) . "\n";
echo EnhancedJson::encode(true) . "\n";
echo EnhancedJson::encode(false) . "\n";
echo EnhancedJson::encode(123) . "\n";
echo EnhancedJson::encode(123.45) . "\n";
echo EnhancedJson::encode(123e45) . "\n";
echo EnhancedJson::encode((float)123) . "\n";
echo EnhancedJson::encode("My dog has fleas") . "\n";
echo EnhancedJson::encode("\"Hey, you!\" she said.") . "\n";
echo EnhancedJson::encode("'Hey, you!' she said.") . "\n";
echo EnhancedJson::encode([1, true, "cats"]) . "\n";
echo EnhancedJson::encode(['name' => 'Carl', 'age' => 45]) . "\n";
echo EnhancedJson::encode([3 => 'Shaun', 12 => 'Angela']) . "\n";
echo EnhancedJson::encode(new stdClass()) . "\n";
// echo EnhancedJson::encode(new class () {
//     public int $count = 45;
//     public string $name = "Bob";
//     private int $age = 100;
// }) . "\n";
echo EnhancedJson::encode(new DateTime()) . "\n";
echo EnhancedJson::encode(new Dictionary('string', 'int')) . "\n";

echo (string)2.03e62;
