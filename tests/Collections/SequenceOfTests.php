<?php

declare(strict_types = 1);

namespace Superclasses\Tests\Collections;

use DateTime;
use Superclasses\Collections\SequenceOf;

////////////////////////////////////////////////////////////////////////////////////////////////////

echo '<pre>';

$a = new SequenceOf('string');
$a->append('orange');
$a->append('red');
$a->append('yellow');
$a[5] = 'pink';
// $a->remove(1);
var_dump($a);

$dt = new DateTime('now');
echo get_debug_type($dt) . PHP_EOL;
