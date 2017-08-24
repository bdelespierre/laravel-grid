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

Artisan::command('grid:experiment', function () {
    App\Models\Grid::truncate(); // reset the grid databasse

    $grid = App\Models\Grid::create([
        'name'   => str_random(),
        'width'  => -1, // infinite
        'height' => -1, // infinite
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
            '--x2' => $chunk[0] + 64,
            '--y2' => $chunk[1] + 64,
        ];

        $this->call('grid:fill',      $params);
        $this->call('grid:terraform', $params);
    }

    $this->call('grid:list');
});
