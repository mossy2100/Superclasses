<?php

declare(strict_types = 1);


interface Stringifiable {
    /**
     * Similar to __toString() (and they could be implemented as aliases), this method returns a developer-friendly
     * string exposing the object contents.
     *
     * @return string
     */
    public function stringify(): string;
}
