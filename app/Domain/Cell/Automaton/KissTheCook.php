<?php

namespace App\Domain\Cell\Automaton;

use App\Contracts\Cell\Automaton;
use App\Models\Cell;
use App\Models\Grid;

class KissTheCook
{
    // check these values here
    // https://gamedevelopment.tutsplus.com/tutorials/generate-random-cave-levels-using-cellular-automata--gamedev-9664
    protected const START_ALIVE = .4; // chance to start alive
    protected const BIRTH_LIMIT = 4;
    protected const DEATH_LIMIT = 3;
    protected const STEPS = 1;

    protected $tap;

    public static function totalSteps(Grid $grid, $x1, $y1, $x2, $y2)
    {
        return ($x2 - $x1) * ($y2 - $y1) * (2 + static::STEPS);
    }

    public function run(Grid $grid, $x1, $y1, $x2, $y2, callable $tap = null)
    {
        $this->tap = $tap;

        $chunk = [];
        for ($x = $x1; $x <= $x2; $x++) {
            for ($y = $y1; $y <= $y2; $y++) {
                $cell = $grid->at($x, $y);

                if (!isset($cell['alive'])) {
                    $cell['alive'] = self::rand() <= self::START_ALIVE;
                }

                $chunk[$cell->x][$cell->y] = $cell['alive'];
                $this->tap();
            }
        }

        for ($i = 0; $i < self::STEPS; $i++) {
            $chunk = $this->doSimulationStep($chunk, $grid, $x1, $y1, $x2, $y2);
        }

        for ($x = $x1; $x <= $x2; $x++) {
            for ($y = $y1; $y <= $y2; $y++) {
                $grid->at($x, $y)->set('alive', (bool) $chunk[$x][$y]);
                $this->tap();
            }
        }

        return $grid;
    }

    protected function doSimulationStep(array $old, Grid $grid, $x1, $y1, $x2, $y2): array
    {
        $new = [];

        for ($x = $x1; $x <= $x2; $x++) {
            for ($y = $y1; $y <= $y2; $y++) {
                $aliveNeighbours = 0;
                foreach ($grid->at($x, $y)->adjacents as $cell) {
                    $aliveNeighbours += (int) $cell['alive'];
                }

                if ($old[$x][$y]) {
                    // if a cell is alive but has too few neighbours, kill it.
                    if ($aliveNeighbours < static::DEATH_LIMIT) {
                        $new[$x][$y] = false;
                    } else {
                        $new[$x][$y] = true;
                    }
                } else {
                    // if the cell is dead now, check if it has the right number of neighbours to be 'born'
                    if ($aliveNeighbours > static::BIRTH_LIMIT) {
                        $new[$x][$y] = true;
                    } else {
                        $new[$x][$y] = false;
                    }
                }

                $this->tap();
            }
        }

        return $new;
    }

    protected static function rand($min = 0, $max = 1)
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }

    protected function tap()
    {
        if (!$fn = $this->tap) {
            return;
        }

        return $fn($this);
    }
}
