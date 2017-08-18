<?php

namespace App\Contracts\Cell;

use App\Models\Map;

interface Automaton
{
    public function run(Map $map);
}
