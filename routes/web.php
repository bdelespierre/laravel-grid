<?php

use App\Models\Grid;
use App\Domain\Point;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/map/{grid}', function(Grid $grid) {
    $cells = $grid->cells()
        ->where('x', '>=', 0)->where('x', '<', 40)
        ->where('y', '>=', 0)->where('y', '<', 40)
        ->orderBy('y', 'x')
        ->get();

    return view('map', compact('cells', 'grid'));
});
