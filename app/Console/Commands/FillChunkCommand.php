<?php

namespace App\Console\Commands;

use App\Models\Cell;
use App\Models\Chunk;
use Illuminate\Console\Command;

class FillChunkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chunk:fill {grid} {--f|flush} {--b|batch-size=200}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fills the given grid with empty cells';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $chunk = Chunk::findOrFail($this->argument('grid'));

        if ($this->option('flush')) {
            $this->call('chunk:flush', ['chunk' => $chunk->id]);
        }

        $this->call('grid:fill', [
            'grid' => $chunk->grid->id,
            '-b'   => $this->option('batch-size'),
            '--x1' => $chunk->x1,
            '--y1' => $chunk->y1,
            '--x2' => $chunk->x2,
            '--y2' => $chunk->y2,
        ]);
    }
}
