<?php

declare(strict_types=1);

namespace Superclasses;

class KeyValuePair
{
    public function __construct(public readonly  mixed $key, public readonly  mixed $value) {}
}
