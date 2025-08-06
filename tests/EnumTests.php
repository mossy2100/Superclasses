<?php
declare(strict_types=1);

namespace Superclasses;

include __DIR__ . '/../src/Dictionary.php';

////////////////////////////////////////////////////////////////////////////////////////////////////

enum DayOfWeek: string {
    case Monday = 'Mon';
    case Tuesday = 'Tue';
    case Wednesday = 'Wed';
}

$x = DayOfWeek::Monday;

echo Dictionary::getStringKey($x) . PHP_EOL;
echo is_object($x) . PHP_EOL;
