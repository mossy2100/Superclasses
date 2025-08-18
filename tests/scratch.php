<?php

namespace Superclasses\Tests;

require_once __DIR__ . '/../src/Angle.php';

//echo fdiv(1, -0.0);

//var_dump($_ENV);

echo tan(M_PI / 2), PHP_EOL;
echo tan(M_PI / 2 + Angle::TRIG_EPSILON), PHP_EOL;

$value = -0.0;
echo $value, PHP_EOL;
echo $value < 0, PHP_EOL;
echo $value === -0.0 ? 't' : 'f', PHP_EOL;

echo tan(INF), PHP_EOL;

echo dechex(123), PHP_EOL;

echo 0 ** 0, PHP_EOL;
