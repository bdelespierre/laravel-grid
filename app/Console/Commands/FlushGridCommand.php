<?php

namespace App\Console\Commands;

use App\Models\Grid;
use Illuminate\Console\Command;

class FlushGridCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grid:flush {grid} {--f|force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all cells from the given grid';

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
        $n = Grid::findOrFail($this->argument('grid'))->cells()->delete();

        $this->info("{$n} cells deleted successfully.");
    }
}
