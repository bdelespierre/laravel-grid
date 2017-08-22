<?php

namespace App\Console\Commands;

use App\Models\Grid;
use Illuminate\Console\Command;

class ListGridsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grid:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists available grids';

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
        $headers = ['UUID', 'Created At', 'Name', 'Width', 'Height', 'Cells'];
        $grids   = [];

        foreach (Grid::all() as $grid) {
            $grids[] = [
                $grid->id,
                $grid->created_at,
                $grid->name,
                $grid->width,
                $grid->height,
                $grid->cells()->count(),
            ];
        }

        $this->table($headers, $grids);
    }
}
