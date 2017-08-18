<?php

use App\Models\Grid;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('/grid/{grid}')->group(function() {

    Route::get('/', function(Grid $grid) {
        return $grid;
    });

    Route::get('/cells', function(Grid $grid) {
        return $grid->cells;
    });

    Route::prefix('/at/{point}')->group(function() {

        Route::get('/', function(Grid $grid, $point) {
            return $grid[$point];
        });

        Route::get('/adjacents', function(Grid $grid, $point) {
            return $grid[$point]->adjacents;
        });

        Route::prefix('/{key}')->group(function() {

            Route::get('/', function(Grid $grid, $point, $key) {
                $dotkey = str_replace('/', '.', $key);
                return $grid[$point][$dotkey];
            })->where(['key' => '.*']);

            Route::match(['PUT', 'POST'], '/', function(Grid $grid, $point, $key, Request $request) {
                $cell   = $grid[$point];
                $dotkey = str_replace('/', '.', $key);

                $status = $cell->has($dotkey)
                    ? 200  // Ok
                    : 201; // Created

                $data = $grid[$point][$dotkey] = $request->isJson()
                    ? $request->json()->all()
                    : $request->getContent();

                return $request->isJson()
                    ? response()->json($data, $status)
                    : response($data, $status);
            })->where(['key' => '.*']);

            Route::delete('/', function(Grid $grid, $point, $key) {
                $cell   = $grid[$point];
                $dotkey = str_replace('/', '.', trim($key));

                // E.G. DELETE /api/grid/.../at/0:0/{a,b,c,d}
                if (preg_match('/^\{([^\}]+)\}$/', $key, $matches)) {
                    list(,$keys) = $matches;
                    $cell->multi(function() use ($cell, $keys) {
                        foreach (explode(',', $keys) as $key) {
                            $cell->pull($key);
                        }
                    });

                    return response('', 204); // No Content
                }

                return $cell->pull($dotkey);
            })->where(['key' => '.*']);

        });

    });

    Route::prefix('/from/{pointA}/to/{pointB}')->group(function() {

        Route::get('/', function(Grid $grid, $pointA, $pointB) {
            $cellA = $grid[$pointA];
            $cellB = $grid[$pointB];

            return $grid->cells()
                ->where('x', '>=', $cellA->x)
                ->where('x', '<=', $cellB->x)
                ->where('y', '>=', $cellA->y)
                ->where('y', '<=', $cellB->y)
                ->get();
        });

    });

});
