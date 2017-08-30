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

    $chunk = $grid->chunks()->create([
        'x' => 0,
        'y' => 0,
        'size' => 64
    ]);

    $chunk->terraform();

    $this->call('chunk:list', ['grid' => $grid->id]);
});
