<?php

use App\Http\Controllers\CalculateCommonProjectsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'status' => 'ok',
    ]);
});

Route::post('/calculate-common-projects', CalculateCommonProjectsController::class);
