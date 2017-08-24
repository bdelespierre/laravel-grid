<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ListChunksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chunk:list {grid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists available chunks on given grid';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $headers = ['UUID', 'Created At', 'Coords', 'Width', 'Height', 'Cells'];
        $chunks  = [];

        foreach (Grid::findOrFail('grid')->chunks as $chunk) {
            $chunks[] = [
                $chunk->id,
                $chunk->created_at,
                (string) $chunk,
                $chunk->x2 - $chunk->x1,
                $chunk->y2 - $chunk->y1,
                $chunk->cells()->count(),
            ];
        }

        $this->table($headers, $chunks);
    }
}
