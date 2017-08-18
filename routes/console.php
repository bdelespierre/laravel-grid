<?php

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

Artisan::command('map:generate {name} {--width=} {--height=}', function ($name) {
    $map = App\Models\Map::create(compact('name') + array_only($this->options(), ['width', 'height']));
    $bar = $this->output->createProgressBar(App\Domain\Cell\Automaton\KissTheCook::totalSteps($map));
    (new App\Domain\Cell\Automaton\KissTheCook)->run($map, function() use ($bar) {
        $bar->advance();
    });
    $bar->finish();
});
