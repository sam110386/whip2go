<?php

use Illuminate\Support\Facades\Route;

// Legacy CakePHP-compatible entrypoints (to be ported slice-by-slice).
//
// Cake routes:
// - /CloudApi/:ver/:action
// - /AccutradeApi/:ver/:action
// - /web_api/:ver/:action

Route::any('/CloudApi/{ver}/{action}', [\App\Http\Controllers\Legacy\CloudApiController::class, 'dispatch']);
Route::any('/AccutradeApi/{ver}/{action}', [\App\Http\Controllers\Legacy\AccutradeApiController::class, 'dispatch']);
Route::any('/web_api/{ver}/{action}', [\App\Http\Controllers\Legacy\WebApiController::class, 'dispatch']);

