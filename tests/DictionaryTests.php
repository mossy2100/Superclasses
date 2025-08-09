<?php

declare(strict_types=1);

namespace Superclasses;

use DateTime;

require_once __DIR__ . '/../src/Dictionary.php';

////////////////////////////////////////////////////////////////////////////////////////////////////

$d = new Dictionary('DateTime', 'int');
$d[new DateTime('2025-07-01')] = 100;
$d[new DateTime('2025-08-01')] = 12;
$d[new DateTime('2025-09-01')] = 314;

// foreach ($d as $key => $value) {
//     var_dump($key, $value);
//     echo PHP_EOL;
// }

// var_dump($d->items);
// var_dump($d->keys());
// var_dump($d->values());
// var_dump($d->entries());

// $a = ["test", "cat"];
// echo Dictionary::getStringKey($a) . PHP_EOL;

// $a = ["test, s:3:cat"];
// echo Dictionary::getStringKey($a) . PHP_EOL;


// function floatToStr(float $f) {
//     $s = (string)$f;
//     if (strpos($s, '.') === false) {
//         $s .= '.0';
//     }
//     return $s;
// }

// echo serialize(null) . PHP_EOL;
// echo serialize(true) . PHP_EOL;
// echo serialize(false) . PHP_EOL;
// echo serialize(123) . PHP_EOL;
// echo serialize(123.45) . PHP_EOL;
// echo serialize("cat") . PHP_EOL;
// echo serialize([1, 2, 3]) . PHP_EOL;
// $obj = (object)['name' => 'Shaun'];
// echo serialize($obj) . PHP_EOL;
// echo spl_object_id($obj) . PHP_EOL;

echo "Open file..." . PHP_EOL;
$fp = fopen(__FILE__, 'r');
echo get_debug_type($fp) . PHP_EOL;
echo is_resource($fp) . PHP_EOL;
echo 'resource type = ' . get_resource_type($fp) . PHP_EOL;
echo 'resource id = ' . get_resource_id($fp) . PHP_EOL;

echo "Closed file..." . PHP_EOL;
fclose($fp);
echo get_debug_type($fp) . PHP_EOL;
echo is_resource($fp) . PHP_EOL;
echo 'resource type = ' . get_resource_type($fp) . PHP_EOL;
echo 'resource id = ' . get_resource_id($fp) . PHP_EOL;

// $names = [
//     1 => 'Shaun',
//     2 => 'Alex',
//     3 => 'Ado',
//     'test' => new DateTime('2025-07-01')
// ];
// $d2 = Dictionary::fromIterable($names);
// // var_dump($d2);

// // Mixed type inference
// $mixed = ['string', 42, 3.14, true, new DateTime()];
// $dict = Dictionary::fromIterable($mixed);
// echo $dict->valueTypes;  // "string|int|float|bool|DateTime"

echo Dictionary::getStringKey(null) . PHP_EOL;
echo Dictionary::getStringKey(true) . PHP_EOL;
echo Dictionary::getStringKey(false) . PHP_EOL;
echo Dictionary::getStringKey(123) . PHP_EOL;
echo Dictionary::getStringKey(123.45e67) . PHP_EOL;
echo Dictionary::getStringKey("cats are nice") . PHP_EOL;
echo Dictionary::getStringKey([1, 2, false, "dogs are nice too"]) . PHP_EOL;
echo Dictionary::getStringKey(new DateTime()) . PHP_EOL;
echo Dictionary::getStringKey($fp) . PHP_EOL;
