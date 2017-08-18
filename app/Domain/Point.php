<?php

namespace App\Domain;

class Point
{
    public function __construct(...$attr)
    {

    }

    public static function fromString()
    {
        if (is_string($offset) && preg_match('/(\d+)\s*(:|x|-|,)\s*(\d+)/', $offset, $matches)) {
            list(, $x,, $y) = $matches;
            return $this->at($x, $y);
        }
    }
}