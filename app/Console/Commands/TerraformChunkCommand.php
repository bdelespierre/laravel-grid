<?php

namespace App\Console\Commands;

use App\Models\Chunk;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TerraformChunkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chunk:terraform {chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates terrain in the given chunk';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $chunk = Chunk::findOrFail($this->argument('chunk'));

        // do NOT terraform twice
        if ($chunk->get('terrain.terraforming.complete')) {
            return $this->error("This chunk generation is complete");
        }

        $chunk->set('terrain.terraforming.started_at', Carbon::now());
        $chunk->save();

        if (!$chunk->flushCells()) {
            return $this->error("Unable to flush chunk cells");
        }

        if (!$chunk->fillWithEmptyCells()) {
            return $this->error("Unable to fill chunk with empty cells");
        }

        $this->call('grid:terraform', [
            'grid' => $chunk->grid->id,
            '--x1' => $chunk->x,
            '--y1' => $chunk->y,
            '--x2' => $chunk->x + $chunk->size -1,
            '--y2' => $chunk->Y + $chunk->size -1,
        ]);

        // smooth edges with adjacent chunks
        foreach (['n','e','s','w'] as $direction) {
            if (!$chunk->hasAdjacent($direction)) {
                continue;
            }

            $adjacent = $chunk->getAdjacent($direction);

            if (!$adjacent->get('terrain.terraforming.complete')) {
                continue;
            }

            $minX = min($chunk->x, $adjacent->x);
            $minY = min($chunk->y, $adjacent->y);
            $maxX = max($chunk->x + $chunk->size -1, $adjacent->x + $adjacent->size -1);
            $maxY = max($chunk->y + $chunk->size -1, $adjacent->y + $adjacent->size -1);

            if (($maxX - $minX) > ($maxY - $minY)) {
                $x1 = ceil(($maxX - $minX) / 2) - 4;
                $y1 = $minY;
                $x2 = $x1 + 8;
                $y2 = $maxY;
            } else {
                $x1 = $minX;
                $y1 = ceil(($maxY - $minY) / 2) - 4;
                $x2 = $maxX;
                $y2 = $y1 + 8;
            }

            $this->call('grid:terraform', [
                'grid'    => $chunk->grid->id,
                '--x1'    => $x1,
                '--y1'    => $y1,
                '--x2'    => $x2,
                '--y2'    => $Y2,
                '--step'  => 1,
                '--seed'  => 0,
                '--river' => 0,
            ]);
        }

        $chunk->set('terrain.terraforming.ended_at', Carbon::now());
        $chunk->set('terrain.terraforming.complete', true);
        $chunk->save();
    }
}
