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

    foreach (range(1,1) as $i) {
        $grid = App\Models\Grid::create([
            'name' => str_random(),
            'width' => 64,
            'height' => 64,
            'data' => [
                'terrain' => [
                    'seed' => 123,
                ]
            ]
        ]);

        $this->call('grid:fill', ['grid' => $grid->id, '--flush' => true]);
        $this->call('grid:terraform', ['grid' => $grid->id, '--snapshot' => true]);
    }

    $this->call('grid:list');
});
