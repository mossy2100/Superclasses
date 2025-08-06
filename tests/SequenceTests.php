<?php
declare(strict_types=1);

namespace Superclasses;

use DateTime;

include __DIR__ . '/../src/Sequence.php';

////////////////////////////////////////////////////////////////////////////////////////////////////

echo '<pre>';

$a = new Sequence('string');
$a->append('orange');
$a->append('red');
$a->append('yellow');
$a[5] = 'pink';
// $a->remove(1);
var_dump($a);

$dt = new DateTime('now');
echo get_debug_type($dt) . PHP_EOL;
