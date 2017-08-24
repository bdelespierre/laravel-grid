<?php

namespace App\Console\Commands;

use App\Models\Chunk;
use Illuminate\Console\Command;

class FlushChunkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chunk:flush {chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all cells from the given chunk';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $chunk = Chunk::findOrFail($this->argument('chunk'));
        $n = $chunk->grid->cells()
            ->whereBetween('x', [$chunk->x1, $chunk->x2])
            ->whereBetween('y', [$chunk->y1, $chunk->y2])
            ->delete();

        $this->info("{$n} cells deleted successfully.");
    }
}
