<?php

namespace App\Console\Commands;

use App\Models\Chunk;
use App\Models\Grid;
use Illuminate\Console\Command;
use RuntimeException;

class CreateChunkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chunk:create {grid} {--x=} {--y=} {--s|size=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new empty chunk';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $grid = Grid::findOrFail($this->argument('grid'));
        $x    = $this->option('x');
        $y    = $this->option('y');
        $size = $this->option('size');

        if ($size < 2) {
            throw new RuntimeException("Invalid size: {$size}");
        }

        if (is_null($x) || is_null($y)) {
            throw new RuntimeException("Invalid chunk coordinates: please provide coordinates");
        }

        $chunk = $grid->chunks()->create([
            'x1' => $x,
            'y1' => $y,
            'x2' => $x + $size - 1,
            'y2' => $y + $size - 1,
        ]);

        $this->info("Chunk {$chunk->id} created successfully.");
    }
}
