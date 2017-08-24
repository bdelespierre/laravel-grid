<?php

namespace App\Console\Commands;

use App\Models\Cell;
use App\Models\Grid;
use Illuminate\Console\Command;
use Webpatser\Uuid\Uuid;

class FillGridCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grid:fill {grid} {--x1=} {--y1=} {--x2=} {--y2=} {--b|batch-size=200} {--f|flush}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fills the given grid with empty cells';

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

        if ($this->option('flush')) {
            $this->call('grid:flush', ['grid' => $grid->id]);
        }

        $x1 = $this->option('x1') ?: 0;
        $y1 = $this->option('y1') ?: 0;
        $x2 = $this->option('x2') ?: $grid->width  -1;
        $y2 = $this->option('y2') ?: $grid->height -1;

        if ($x2 - $x1 <= 0 && $y2 - $y1 <= 0) {
            return $this->error("Not a rectangle: [$x1,$y1] [$x2,$y2]");
        }

        $bar = $this->output->createProgressBar(($x2 - $x1) * ($y2 - $y1));

        $batchSize = $this->option('batch-size');
        $batch = [];

        for ($x = $x1; $x <= $x2; $x++) {
            for ($y = $y1; $y <= $y2; $y++) {
                $batch[] = compact('x', 'y') + [
                    'id'      => Uuid::generate()->string,
                    'grid_id' => $grid->id
                ];

                if (count($batch) >= $batchSize) {
                    Cell::insert($batch);
                    $batch = [];
                }

                $bar->advance();
            }
        }

        if ($batch) {
            Cell::insert($batch);
        }

        $bar->finish();

        $this->info("\rGrid {$grid->id} filled successfully.");
    }
}
