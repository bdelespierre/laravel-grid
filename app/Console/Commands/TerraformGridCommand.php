<?php

namespace App\Console\Commands;

use App\Models\Cell;
use App\Models\Grid;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use SplObjectStorage;

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
        $grid = Grid::findOrFail($this->argument('grid'));

        $x1 = $this->option('x1') ?: 0;
        $y1 = $this->option('y1') ?: 0;
        $x2 = $this->option('x2') ?: $grid->width  -1;
        $y2 = $this->option('y2') ?: $grid->height -1;

        if ($x2 - $x1 <= 0 && $y2 - $y1 <= 0) {
            return $this->error("Not a rectangle: [$x1,$y1] [$x2,$y2]");
        }

        // fetch grid's cells and index them by coordinates
        $cells = $grid->cells()
            ->whereBetween('x', [$x1, $x2])
            ->whereBetween('y', [$y1, $y2])
            ->get()
            ->keyBy(function($cell) {
                return "{$cell->x}:{$cell->y}";
            })
            ->all();

        // prepare neighborhood cache
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

        // generate noise
        $definitions = [
            'water'  => ['ratio' => [0,   .07], 'weight' => .00, 'solid' => true ],   //  7% water
            'grass'  => ['ratio' => [.07, .70], 'weight' => .65, 'solid' => false],   // 63% grass
            'gravel' => ['ratio' => [.70, .80], 'weight' => .85, 'solid' => false],   // 10% gravel
            'stone'  => ['ratio' => [.8, 1.00], 'weight' => .99, 'solid' => true ],   // 20% stone
        ];

        foreach ($cells as &$cell) {
            $rand = self::random();
            foreach ($definitions as $name => $definition) {
                if ($rand >= $definition['ratio'][0] && $rand < $definition['ratio'][1]) {
                    $cell['tile'] = $name;
                    break;
                }
            }
        }

        // run cellular automata steps
        $steps = $this->hasOption("steps") ? $this->option("steps") : 3;
        $bar   = $this->output->createProgressBar(count($cells) * 2 * $steps);

        for ($step = 0; $step < $steps; $step++) {
            $next = new SplObjectStorage;

            foreach ($cells as $cell) {
                $sum = $definitions[$cell['tile']]['weight'];
                $num = 1;

                foreach ($neighbors[$cell] as $neighbor) {
                    $sum += $definitions[$neighbor['tile']]['weight'];
                    $num ++;
                }

                $avg = $sum / $num;

                foreach ($definitions as $name => $definition) {
                    if ($avg >= $definition['ratio'][0] && $avg < $definition['ratio'][1]) {
                        $tile = $name;
                    }
                }

                if ($tile != $cell['tile']) {
                    $next[$cell] = $tile;
                }

                $bar->advance();
            }

            // update cells
            foreach ($next as $cell) {
                $cell['tile'] = $next[$cell];
                $bar->advance();
            }
        }

        $bar->finish();

        // write changes on database
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

    protected static function debug($cells)
    {
        foreach ($cells as $cell) {
            if (!isset($count[$cell['tile']])) {
                $count[$cell['tile']] = 0;
            }

            $count[$cell['tile']]++;
        }

        return $count;
    }
}
