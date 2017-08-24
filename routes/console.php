<?php

use App\Domain\Cell\Automaton\KissTheCook;
use App\Models\Grid;
use Illuminate\Foundation\Inspiring;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('grid:experiment {--flush=1} {--patch=1}', function () {
    if ($this->option('flush')) {
        App\Models\Grid::truncate(); // reset the grid databasse
    }

    $grid = App\Models\Grid::create([
        'name'    => str_random(),
        'width'   => -1, // infinite
        'height'  => -1, // infinite
        'data'    => ['terrain' => ['seed' => 123]],
    ]);

    $chunks = [
        [0,  0], [64,  0],
        [0, 64], [64, 64],
    ];

    foreach ($chunks as $chunk) {
        $params = [
            'grid' => $grid->id,
            '--x1' => $chunk[0],
            '--y1' => $chunk[1],
            '--x2' => $chunk[0] + 63,
            '--y2' => $chunk[1] + 63,
        ];

        $this->call('grid:fill',      $params);
        $this->call('grid:terraform', $params);
    }

    if ($this->option('patch')) {
        $patches = [
            ['--x1' => 59, '--y1' =>  0, '--x2' =>  67, '--y2' => 127],
            ['--x1' =>  0, '--y1' => 59, '--x2' => 127, '--y2' =>  67]
        ];

        foreach ($patches as $coords) {
            $this->call('grid:terraform', $coords + [
                'grid'     => $grid->id,
                '--seed'   => false,
                '--rivers' => false,
                '--steps'  => 1,
            ]);
        }
    }

    $this->call('grid:list');
});
