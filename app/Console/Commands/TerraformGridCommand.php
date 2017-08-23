<?php

namespace App\Console\Commands;

use App\Models\Cell;
use App\Models\Grid;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use SplObjectStorage;
use Webpatser\Uuid\Uuid;

class TerraformGridCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grid:terraform {grid} {--x1=} {--y1=} {--x2=} {--y2=} {--steps=3} {--snapshot}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates terrain';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $grid = Grid::findOrFail($this->argument('grid'));

        $x1 = $this->option('x1') ?: 0;
        $y1 = $this->option('y1') ?: 0;
        $x2 = $this->option('x2') ?: $grid->width  -1;
        $y2 = $this->option('y2') ?: $grid->height -1;

        if ($x2 - $x1 <= 0 && $y2 - $y1 <= 0) {
            return $this->error("Not a rectangle: [$x1,$y1] [$x2,$y2]");
        }

        // --------------------------------------------------------------------
        // terrain generation definition & seeds
        // --------------------------------------------------------------------


        $definitions = $grid->get('terrain.definitions', [
            'water'  => ['ratio' => [0,   .07], 'weight' => -1.3, 'waterThreshold' =>  0, 'solid' => true ], //  7%
            'grass'  => ['ratio' => [.07, .70], 'weight' =>  .60, 'waterThreshold' =>  2, 'solid' => false], // 63%
            'gravel' => ['ratio' => [.70, .80], 'weight' =>  .85, 'waterThreshold' =>  5, 'solid' => false], // 10%
            'stone'  => ['ratio' => [.8, 1.00], 'weight' =>  .99, 'waterThreshold' => 10, 'solid' => true ], // 20%
        ]);

        if ($grid->has('terrain.seed')) {
            mt_srand((int) $grid['terrain.seed']);
            srand($grid['terrain.seed']);
        }

        // --------------------------------------------------------------------
        // fetch grid's cells and index them by coordinates
        // --------------------------------------------------------------------

        $cells = $grid->cells()
            ->whereBetween('x', [$x1, $x2])
            ->whereBetween('y', [$y1, $y2])
            ->get()
            ->keyBy(function($cell) {
                return "{$cell->x}:{$cell->y}";
            })
            ->all();

        // --------------------------------------------------------------------
        // prepare neighborhood cache
        // --------------------------------------------------------------------

        $neighbors = $this->initializeNeighbors($cells);

        // --------------------------------------------------------------------
        // generate noise
        // --------------------------------------------------------------------

        $this->initializeCellularAutomata($cells, $definitions);

        // --------------------------------------------------------------------
        // run cellular automata steps
        // --------------------------------------------------------------------

        $steps = $this->hasOption("steps") ? $this->option("steps") : 3;
        $this->runCellularAutomata($cells, $steps, $neighbors);

        // --------------------------------------------------------------------
        // run water automata steps
        // --------------------------------------------------------------------

        $this->initializeRiverAutomata($cells);
        $this->runRiverAutomata($cells);

        // --------------------------------------------------------------------
        // write changes on database
        // --------------------------------------------------------------------

        $this->save($grid, $cells);
        $this->info("\rGrid {$grid->id} terraformed successfully.");
    }

    protected function initializeNeighbors($cells)
    {
        $neighbors = new SplObjectStorage;

        foreach ($cells as $cell) {
            $cell->autocommit = false;
            $cellNeighbors = [];

            for ($x = $cell->x - 1; $x <= $cell->x + 1; $x++) {
                for ($y = $cell->y - 1; $y <= $cell->y + 1; $y++) {
                    if ($x == $cell->x && $y == $cell->y) {
                        continue; // ignore self
                    }

                    if (isset($cells["{$x}:{$y}"])) {
                        $cellNeighbors[] = $cells["{$x}:{$y}"];
                    }
                }
            }

            $neighbors[$cell] = $cellNeighbors;
        }

        return $neighbors;
    }

    protected function initializeCellularAutomata(&$cells, $definitions)
    {
        // generate noise
        foreach ($cells as &$cell) {
            $rand = self::random();
            foreach ($definitions as $name => $definition) {
                if ($rand >= $definition['ratio'][0] && $rand < $definition['ratio'][1]) {
                    $cell['tile'] = $name;
                    break;
                }
            }
        }
    }

    protected function runCellularAutomata(&$cells, $steps, $neighbors)
    {
        $bar   = $this->output->createProgressBar(count($cells) * $steps);

        for ($step = 0; $step < $steps; $step++) {
            $next = new SplObjectStorage;

            foreach ($cells as $cell) {
                $sum = $definitions[$cell['tile']]['weight'];
                $num = 1;

                foreach ($neighbors[$cell] as $neighbor) {
                    $sum += $definitions[$neighbor['tile']]['weight'];
                    $num ++;
                }

                $avg  = $sum / $num;
                $tile = null;

                if ($avg < 0) {
                    $tile = "water";
                } elseif ($avg > 1) {
                    $tile = "stone";
                } else {
                    foreach ($definitions as $name => $definition) {
                        if ($avg >= $definition['ratio'][0] && $avg < $definition['ratio'][1]) {
                            $tile = $name;
                            break;
                        }
                    }
                }

                if ($tile && $tile != $cell['tile']) {
                    $next[$cell] = $tile;
                }

                $bar->advance();
            }

            // update cells
            foreach ($next as $cell) {
                $cell['tile'] = $next[$cell];
            }
        }

        $bar->finish();
    }

    protected function initializeRiverAutomata(&$cells)
    {
        // each direction is given as a vector [x,y]
        $directions = [
            [0, -1], // go north
            [+1, 0], // go east
            [0, +1], // go south
            [-1, 0], // go west
        ];

        if ($grid->has('terrain.seed')) {
            srand($grid->get('terrain.seed'));
        }

        $rivers = new SplObjectStorage;

        $connectWaterTiles = function (Cell $cell) use (&$connectWaterTiles, &$connected, $neighbors) {
            foreach ($neighbors[$cell] as $neighbor) {
                if ($neighbor['tile'] != 'water') {
                    continue;
                }

                if (!$connected->contains($neighbor)) {
                    $connected->attach($neighbor);
                    $connectWaterTiles($neighbor);
                }
            }
        };


        foreach ($cells as $cell) {
            if ($cell['tile'] == 'water') {
                $connected = new SplObjectStorage;
                $connected->attach($cell);
                $connectWaterTiles($cell);

                // exclude water bodies of less than 3 cells
                if (count($connected) >= 3) {
                    foreach ($connected as $cell) {
                        $rivers->attach($cell);
                        $cell['tile']  = 'river';
                        $cell['river'] = [
                            'flow'     => $directions[array_rand($directions)],
                            'strength' => 20,
                        ];
                    }
                }
            }
        }
    }

    protected function runRiverAutomata(&$cells, $definitions)
    {
        $flowOut = function (Cell $cell, SplObjectStorage $updates, $continue = true) use (&$flowOut, $cells, $definitions) {
            list($x, $y) = $cell['river.flow'];
            $coordinates = ($cell->x + $x) . ':' . ($cell->y + $y);

            if (!isset($cells[$coordinates])) {
                return false;
            }

            $neighbor = $cells[$coordinates];

            if ($neighbor['tile'] == 'river') {
                return false;
            }

            if ($neighbor['tile'] == 'water') {
                $updates[$cell] = ['river' => null];
                $updates[$neighbor] = [
                    'tile'  => 'river',
                    'river' => $cell['river']
                ];

                return $neighbor;
            }

            if ($cell['river.strength'] >= ($threshold = $definitions[$neighbor['tile']]['waterThreshold'])) {
                $updates[$cell] = ['river' => null];
                $updates[$neighbor] = [
                    'tile'  => 'river',
                    'river' => [
                        'flow'     => $cell['river.flow'],
                        'strength' => $cell['river.strength'] - $threshold
                    ]
                ];

                return $neighbor;
            }

            if ($continue) {
                // immediately flow perendicular to the current direction
                foreach ($x ? [[0,1],[0,-1]] : [[-1,0],[1,0]] as $flow) {
                    $cell['river.flow'] = $flow;

                    if ($result = $flowOut($cell, $updates, false)) {
                        return $result;
                    }
                }
            }

            return false;
        };

        do {
            $movements = 0;
            $newRivers = new SplObjectStorage;
            $updates   = new SplObjectStorage;

            foreach ($rivers as $cell) {
                if ($newRiver = $flowOut($cell, $updates)) {
                    $newRivers->attach($newRiver);
                    $movements++;
                }
            }

            // update cells
            foreach ($updates as $cell) {
                foreach ($updates[$cell] as $k => $v) {
                    if ($v === null) {
                        unset($v);
                    } else {
                        $cell[$k] = $v;
                    }
                }
            }

            $rivers = $newRivers;
        } while ($movements);
    }

    protected function save($grid, $cells)
    {
        $chunkSize = 200;
        $chunk = [];
        $bar = $this->output->createProgressBar(ceil(count($cells) / $chunkSize));

        $table = (new Cell)->getTable();
        $pdo = DB::connection()->getPdo();
        $insert = function ($chunk) use ($pdo, $table) {
            return $pdo->exec("replace into {$table} (`id`,`grid_id`,`x`,`y`,`data`) values " . implode(',', $chunk));
        };

        foreach ($cells as $cell) {
            $chunk[] = vsprintf("(%s,%s,%s,%s,%s)", array_map([$pdo, 'quote'], [
                $cell->id, $grid->id, $cell->x, $cell->y,
                json_encode($cell->data),
            ]));

            if (count($chunk) >= $chunkSize) {
                $insert($chunk);
                $chunk = [];
                $bar->advance();
            }
        }

        if ($chunk) {
            $insert($chunk);
            $bar->advance();
        }

        $bar->finish();
    }

    protected static function random($min = 0, $max = 1)
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }
}
