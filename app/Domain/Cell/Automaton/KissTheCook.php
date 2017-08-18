<?php

namespace App\Domain\Cell\Automaton;

use App\Contracts\Cell\Automaton;
use App\Models\Cell;
use App\Models\Map;

class KissTheCook implements Automaton
{
    // check these values here
    // https://gamedevelopment.tutsplus.com/tutorials/generate-random-cave-levels-using-cellular-automata--gamedev-9664
    protected const START_ALIVE = .4; // chance to start alive
    protected const BIRTH_LIMIT = 4;
    protected const DEATH_LIMIT = 3;
    protected const STEPS = 1;

    protected $tap;

    public static function totalSteps(Map $map)
    {
        return ($map->width * $map->height) * (2 + static::STEPS);
    }

    public function run(Map $map, callable $tap = null)
    {
        $this->tap = $tap;

        $grid = $this->initialize($map);

        for ($i = 0; $i < self::STEPS; $i++) {
            $grid = $this->doSimulationStep($grid);
        }

        return $this->finalize($map, $grid);
    }

    protected function initialize(Map $map): array
    {
        $grid = [];

        for ($x = 0; $x < $map->width; $x++) {
            for ($y = 0; $y < $map->height; $y++) {
                if (static::rand() < static::START_ALIVE) {
                    $grid[$x][$y] = true;
                } else {
                    $grid[$x][$y] = false;
                }

                $this->tap();
            }
        }

        return $grid;
    }

    protected function finalize(Map $map, array $grid): Map
    {
        for ($x = 0; $x < $map->width; $x++) {
            for ($y = 0; $y < $map->height; $y++) {
                $map->at($x, $y)['alive'] = $grid[$x][$y];

                $this->tap();
            }
        }

        return $map;
    }

    protected function doSimulationStep(array $old): array
    {
        $new    = [];
        $height = count($old);
        $width  = count($old[0]);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $aliveNeighbours = static::countAliveNeighbours($old, $x, $y);

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

    protected static function countAliveNeighbours($grid, $x, $y): int
    {
        $alive  = 0;
        $height = count($grid);
        $width  = count($grid[0]);

        for ($i = -1; $i < 2; $i++) {
            for ($j = -1; $j < 2; $j++) {
                $nx = $x + $i;
                $ny = $y + $j;

                // exclude current
                if ($i == 0 && $j == 0) {
                    continue;
                }

                // stay within the map constraints (or don't)
                else if ($nx < 0 || $nx > $width -1 || $ny < 0 || $ny > $height -1) {
                    $alive++;
                }

                else {
                    $alive += (int) $grid[$nx][$ny];
                }
            }
        }

        return $alive;
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
