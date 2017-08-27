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
    protected $signature = 'grid:terraform {grid} {--x1=} {--y1=} {--x2=} {--y2=} {--steps=3} {--seed=1} {--rivers=1}';

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
            $seed = (int) $grid['terrain.seed'] + crc32("{$x1}{$y1}{$x2}{$y2}");
            mt_srand($seed);
            srand($seed);
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

        $this->initializeNeighbors();
        $this->initializeCellularAutomata();

        $steps = $this->hasOption("steps") ? $this->option("steps") : 3;
        $this->runCellularAutomata($steps);

        if ($this->option('rivers')) {
            foreach ([10, 5] as $strength) {
                $rivers = $this->initializeRiverAutomata($strength);
                $this->runRiverAutomata($rivers);
            }

            $this->runCellularAutomata(1);
        }

        $this->pave();

        // --------------------------------------------------------------------
        // we're done, save everything!
        // --------------------------------------------------------------------

        $this->save($grid);

        $this->info("\rGrid {$grid->id} terraformed successfully.");
    }

    protected const DIRECTIONS = [
        'nw' => [-1,-1],
        'n'  => [ 0,-1],
        'ne' => [+1,-1],
        'w'  => [-1, 0],
        'e'  => [+1, 0],
        'sw' => [+1,-1],
        's'  => [ 0,+1],
        'se' => [+1,+1],
    ];

    protected function initializeNeighbors()
    {
        $this->neighbors = new SplObjectStorage;

        foreach ($this->cells as $cell) {
            $cellNeighbors = [];

            foreach (self::DIRECTIONS as $name => $offsets) {
                list($ox, $oy) = $offsets;

                $x = $cell->x + $ox;
                $y = $cell->y + $oy;

                if (isset($this->cells["{$x}:{$y}"])) {
                    $cellNeighbors[$name] = $this->cells["{$x}:{$y}"];
                }
            }

            $this->neighbors[$cell] = $cellNeighbors;
        }
    }

    protected function getNeighbor(Cell $cell, string $dir, $onlyCached = true)
    {
        if (isset($this->neighbors[$cell][$dir])) {
            return $this->neighbors[$cell][$dir];
        }

        if ($onlyCached) {
            return null;
        }

        list($ox, $oy) = self::DIRECTIONS[$dir];
        return $this->grid->at($cell->x + $ox, $cell->y + $oy);
    }

    protected function initializeCellularAutomata()
    {
        if ($this->option('seed')) {
            // generate noise
            foreach ($this->cells as &$cell) {
                $rand = self::random();
                foreach ($this->definitions as $name => $definition) {
                    if ($rand >= $definition['ratio'][0] && $rand < $definition['ratio'][1]) {
                        $cell['terrain.base_tile'] = $name;
                        break;
                    }
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
                $sum = $this->definitions[$cell['terrain.base_tile']]['weight'];
                $num = 1;

                foreach ($this->neighbors[$cell] as $neighbor) {
                    $sum += $this->definitions[$neighbor['terrain.base_tile']]['weight'];
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

                if ($tile && $tile != $cell['terrain.base_tile']) {
                    $next[$cell] = $tile;
                }

                $bar->advance();
            }

            // update cells
            foreach ($next as $cell) {
                $cell['terrain.base_tile'] = $next[$cell];
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
                if ($neighbor['terrain.base_tile'] != 'water') {
                    continue;
                }

                if (!$connected->contains($neighbor)) {
                    $connected->attach($neighbor);
                    $connectWaterTiles($neighbor);
                }
            }
        };

        foreach ($this->cells as $cell) {
            if ($cell['terrain.base_tile'] == 'water') {
                $connected = new SplObjectStorage;
                $connected->attach($cell);
                $connectWaterTiles($cell);

                // exclude water bodies of less than 3 cells
                if (count($connected) >= 3) {
                    foreach ($connected as $cell) {
                        $rivers->attach($cell);
                        $cell['terrain.base_tile']  = 'river';
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

            if ($neighbor['terrain.base_tile'] == 'river') {
                return false;
            }

            if ($neighbor['terrain.base_tile'] == 'water') {
                $updates[$cell] = ['river' => null];
                $updates[$neighbor] = [
                    'terrain.base_tile'  => 'river',
                    'river' => $cell['river']
                ];

                return $neighbor;
            }

            if ($cell['river.strength'] >= ($resistance = $this->definitions[$neighbor['terrain.base_tile']]['resistance'])) {
                $updates[$cell] = ['river' => null];
                $updates[$neighbor] = [
                    'terrain.base_tile'  => 'river',
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
            if ($cell['terrain.base_tile'] == 'river') {
                $cell['terrain.base_tile'] = 'water';
            }

            if (isset($cell['river'])) {
                unset($cell['river']);
            }
        }
    }

    protected function pave()
    {
        // process elevation (stone tile shifting)
        foreach ($this->cells as $cell) {
            if ($cell['terrain.base_tile'] == 'stone') {
                if (!$neighbor = $this->getNeighbor($cell, 'n', false)) {
                    continue;
                }

                if ($neighbor['terrain.base_tile'] != 'stone') {
                    $neighbor['terrain.base_tile'] = 'stone';

                    // if cell was not pulled up from cache
                    if (!isset($this->cells[$neighbor->x . ':' . $neighbor->y])) {
                        $neighbor->save();
                    }
                }
            }
        }

        foreach ($this->cells as $cell) {
            $neighbor = $this->getNeighbor($cell, 's', false);

            if ($neighbor && $cell['terrain.base_tile'] == 'stone' && $neighbor['terrain.base_tile'] != 'stone') {
                $cell['terrain.layers'] = ['stone-wall'];

                // if cell was not pulled up from cache
                if (!isset($this->cells[$neighbor->x . ':' . $neighbor->y])) {
                    $neighbor->save();
                }
            } else {
                $cell['terrain.layers'] = [$cell['terrain.base_tile']];
            }
        }

        $grassVariants = [
            'grass'   => [  0,  .8], // 80%
            'grass-1' => [ .8,  .9], // 10%
            'grass-2' => [ .9, .96], // 6%
            'grass-3' => [.96,   1], // 4%
        ];

        foreach ($this->cells as $cell) {
            $layers = $cell['terrain.layers'];

            if ($layers[0] == 'stone' || $layers[0] == 'water') {
                foreach (['n' => 'top','e' => 'right','s' => 'bottom' ,'w' => 'left'] as $dir => $variant) {
                    if ($neighbor = $this->getNeighbor($cell, $dir, false)) {
                        if ($neighbor['terrain.layers'][0] != $layers[0]) {
                            $layers[] = "stone-edge-{$variant}";
                        }
                    }
                }
            } elseif ($layers[0] == 'grass') {
                $r = self::random();
                foreach ($grassVariants as $variant => $p) {
                    if ($r > $p[0] && $r <= $p[1]) {
                        $layers[] = $variant;
                    }
                }
            }

            $cell['terrain.layers'] = $layers;
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
