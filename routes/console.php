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

Artisan::command('create:chunk {grid} {x1} {y1} {x2} {y2}', function ($grid, $x1, $y1, $x2, $y2) {
    $grid = Grid::findOrFail($grid);
    $bar = $this->output->createProgressBar(KissTheCook::totalSteps($grid, $x1, $y1, $x2, $y2));
    (new KissTheCook)->run($grid, $x1, $y1, $x2, $y2, function () use ($bar) { $bar->advance(); });
    $bar->finish();
})->describe('');

Artisan::command('map', function() {
    $grid = Grid::create(['name' => str_random(), 'width' => -1, 'height' => -1]);
    Artisan::call('create:chunk', ['grid' => $grid->id, 'x1' =>   0, 'y1' =>  0, 'x2' => 19, 'y2' => 19]);
    Artisan::call('create:chunk', ['grid' => $grid->id, 'x1' =>   0, 'y1' => 20, 'x2' => 19, 'y2' => 39]);
    Artisan::call('create:chunk', ['grid' => $grid->id, 'x1' =>  20, 'y1' =>  0, 'x2' => 39, 'y2' => 19]);
    Artisan::call('create:chunk', ['grid' => $grid->id, 'x1' =>  20, 'y1' => 20, 'x2' => 39, 'y2' => 39]);
    $this->line($grid->id);
});
