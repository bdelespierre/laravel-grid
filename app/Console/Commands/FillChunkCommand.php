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
    protected $signature = 'chunk:fill {chunk} {--f|flush} {--b|batch-size=200}';

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

        $this->fill($chunk);

        $this->info("\Chunk {$chunk->id} filled successfully.");
    }

    public function fill(Chunk $chunk, $size = null)
    {
        $size = $size ?: $this->option('batch-size');

        foreach ($chunk->fill() as $cell) {
            $batch[] = $cell;

            if (count($batch) >= $size) {
                Cell::insert($batch) && $batch = [];
            }
        }

        if ($batch) {
            Cell::insert($batch);
        }
    }
}
