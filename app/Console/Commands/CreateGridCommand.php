<?php

namespace App\Console\Commands;

use App\Models\Grid;
use Illuminate\Console\Command;

class CreateGridCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grid:create {name} {--width=} {--height=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new empty grid';

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
        $name   = $this->argument('name');
        $width  = $this->option('width')  ?: -1;
        $height = $this->option('height') ?: -1;

        if ($width == 0 || $width < -1) {
            return $this->error("Invalid width: $width");
        }

        if ($height == 0 || $height < -1) {
            return $this->error("Invalid height: $height");
        }

        $grid = Grid::create(compact('name', 'width', 'height'));

        $this->info("Grid {$grid->id} created successfully.");
    }
}
