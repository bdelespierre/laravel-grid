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
    protected $signature = 'grid:terraform {grid} {--x1=} {--y1=} {--x2=} {--y2=} {--steps=3}';

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
        $this->grid = $grid = Grid::findOrFail($this->argument('grid'));

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

        $this->definitions = $grid->get('terrain.definitions', [
            'water'  => ['ratio' => [0,   .07], 'weight' => -1.3, 'resistance' =>  0, 'solid' => true ], //  7%
            'grass'  => ['ratio' => [.07, .70], 'weight' =>  .60, 'resistance' =>  2, 'solid' => false], // 63%
            'gravel' => ['ratio' => [.70, .80], 'weight' =>  .85, 'resistance' =>  5, 'solid' => false], // 10%
            'stone'  => ['ratio' => [.8, 1.00], 'weight' =>  .99, 'resistance' => 10, 'solid' => true ], // 20%
        ]);

        if ($grid->has('terrain.seed')) {
            mt_srand((int) $grid['terrain.seed']);
            srand($grid['terrain.seed']);
        }

        // --------------------------------------------------------------------
        // fetch grid's cells and index them by coordinates
        // --------------------------------------------------------------------

        $this->cells = $grid->cells()
            ->whereBetween('x', [$x1, $x2])
            ->whereBetween('y', [$y1, $y2])
            ->get()
            ->keyBy(function($cell) {
                return "{$cell->x}:{$cell->y}";
            })
            ->all();

        // --------------------------------------------------------------------
        // run the terraforming algorithm
        // --------------------------------------------------------------------

        $this->initializeNeighbors($x1, $y1, $x2, $y2);
        $this->initializeCellularAutomata();

        $steps = $this->hasOption("steps") ? $this->option("steps") : 3;
        $this->runCellularAutomata($steps);

        foreach ([10, 5] as $strength) {
            $rivers = $this->initializeRiverAutomata($strength);
            $this->runRiverAutomata($rivers);
        }

        $this->runCellularAutomata(1);

        // --------------------------------------------------------------------
        // we're done, save everything!
        // --------------------------------------------------------------------

        $this->save($grid);

        $this->info("\rGrid {$grid->id} terraformed successfully.");
    }

    protected function initializeNeighbors($x1, $y1, $x2, $y2)
    {
        $this->neighbors = new SplObjectStorage;

        // expand selection to adjacent chunks (if any)
        $cells = $this->cells + $this->grid->cells()
            ->whereBetween('x', [$x1 - 1, $x2 + 1])
            ->whereBetween('y', [$y1 - 1, $y2 + 1])
            ->whereNotBetween('x', [$x1, $x2])
            ->whereNotBetween('y', [$y1, $y2])
            ->get()
            ->keyBy(function($cell) {
                return "{$cell->x}:{$cell->y}";
            })
            ->all();

        foreach ($cells as $cell) {
            $cell->autocommit = false;
            $cellNeighbors = [];

            for ($x = $cell->x - 1; $x <= $cell->x + 1; $x++) {
                for ($y = $cell->y - 1; $y <= $cell->y + 1; $y++) {
                    if ($x == $cell->x && $y == $cell->y) {
                        continue; // ignore self
                    }

                    if (isset($this->cells["{$x}:{$y}"])) {
                        $cellNeighbors[] = $this->cells["{$x}:{$y}"];
                    }
                }
            }

            $this->neighbors[$cell] = $cellNeighbors;
        }
    }

    protected function initializeCellularAutomata()
    {
        // generate noise
        foreach ($this->cells as &$cell) {
            $rand = self::random();
            foreach ($this->definitions as $name => $definition) {
                if ($rand >= $definition['ratio'][0] && $rand < $definition['ratio'][1]) {
                    $cell['tile'] = $name;
                    break;
                }
            }
        }
    }

    protected function runCellularAutomata($steps)
    {
        $bar   = $this->output->createProgressBar(count($this->cells) * $steps);

        for ($step = 0; $step < $steps; $step++) {
            $next = new SplObjectStorage;

            foreach ($this->cells as $cell) {
                $sum = $this->definitions[$cell['tile']]['weight'];
                $num = 1;

                foreach ($this->neighbors[$cell] as $neighbor) {
                    $sum += $this->definitions[$neighbor['tile']]['weight'];
                    $num ++;
                }

                $avg  = $sum / $num;
                $tile = null;

                if ($avg < 0) {
                    $tile = "water";
                } elseif ($avg > 1) {
                    $tile = "stone";
                } else {
                    foreach ($this->definitions as $name => $definition) {
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

    protected function initializeRiverAutomata($strength)
    {
        // each direction is given as a vector [x,y]
        $directions = [
            [0, -1], // go north
            [+1, 0], // go east
            [0, +1], // go south
            [-1, 0], // go west
        ];

        $rivers = new SplObjectStorage;

        $connectWaterTiles = function (Cell $cell) use (&$connectWaterTiles, &$connected) {
            foreach ($this->neighbors[$cell] as $neighbor) {
                if ($neighbor['tile'] != 'water') {
                    continue;
                }

                if (!$connected->contains($neighbor)) {
                    $connected->attach($neighbor);
                    $connectWaterTiles($neighbor);
                }
            }
        };

        foreach ($this->cells as $cell) {
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
                            'strength' => $strength,
                        ];
                    }
                }
            }
        }

        return $rivers;
    }

    protected function runRiverAutomata($rivers)
    {
        $flowOut = function (Cell $cell, SplObjectStorage $updates, $continue = true) use (&$flowOut) {
            list($x, $y) = $cell['river.flow'];
            $coordinates = ($cell->x + $x) . ':' . ($cell->y + $y);

            if (!isset($this->cells[$coordinates])) {
                return false;
            }

            $neighbor = $this->cells[$coordinates];

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

            if ($cell['river.strength'] >= ($resistance = $this->definitions[$neighbor['tile']]['resistance'])) {
                $updates[$cell] = ['river' => null];
                $updates[$neighbor] = [
                    'tile'  => 'river',
                    'river' => [
                        'flow'     => $cell['river.flow'],
                        'strength' => $cell['river.strength'] - $resistance
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

        foreach ($this->cells as $cell) {
            if ($cell['tile'] == 'river') {
                $cell['tile'] = 'water';
            }

            if (isset($cell['river'])) {
                unset($cell['river']);
            }
        }
    }

    protected function save($grid)
    {
        $chunkSize = 200;
        $chunk = [];
        $bar = $this->output->createProgressBar(ceil(count($this->cells) / $chunkSize));

        $table = (new Cell)->getTable();
        $pdo = DB::connection()->getPdo();
        $insert = function ($chunk) use ($pdo, $table) {
            return $pdo->exec("replace into {$table} (`id`,`grid_id`,`x`,`y`,`data`) values " . implode(',', $chunk));
        };

        foreach ($this->cells as $cell) {
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
