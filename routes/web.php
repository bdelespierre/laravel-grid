<?php

use App\Models\Grid;

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

Route::get('/map/list', function () {
    $grids = Grid::all();
    return view('map.index', compact('grids'));
});

Route::get('/map/{grid}', function(Grid $grid) {
    $cells = $grid->cells()
        ->whereBetween('x', [0, 127])
        ->whereBetween('y', [0, 127])
        ->get()
        ->keyBy(function($cell) {
            return "{$cell->x}:{$cell->y}";
        });

    return view('map.view', compact('cells', 'grid'));
});
