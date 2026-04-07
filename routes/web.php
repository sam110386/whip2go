<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    // Route to Cake-like login landing for parity during migration.
    return redirect('/logins/index');
});

// Cake redirect parity (used after login)
Route::get('/users/dashboard', function () {
    return redirect('/dashboard/index', 301);
});

// --------------------------------------------------------------------------
// Cake-like dispatcher routes (incremental controller porting).
// Supports only 2-segment routes for now:
// - /{controller}/{action}
// - /admin/{controller}/{action}
// --------------------------------------------------------------------------
Route::any('/admin/{controller}/{action}', [\App\Http\Controllers\Legacy\LegacyDispatcherController::class, 'dispatchAdmin'])
    ->where('controller', '[A-Za-z0-9_]+')
    ->where('action', '[A-Za-z0-9_]+')
    ->middleware(['legacy.admin.session'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::any('/admin/{controller}/{action}/{path}', [\App\Http\Controllers\Legacy\LegacyDispatcherController::class, 'dispatchAdminWithPath'])
    ->where('controller', '[A-Za-z0-9_]+')
    ->where('action', '[A-Za-z0-9_]+')
    ->where('path', '.*')
    ->middleware(['legacy.admin.session'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::any('/{controller}/{action}', [\App\Http\Controllers\Legacy\LegacyDispatcherController::class, 'dispatch'])
    ->where('controller', '[A-Za-z0-9_]+')
    ->where('action', '[A-Za-z0-9_]+')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::any('/{controller}/{action}/{path}', [\App\Http\Controllers\Legacy\LegacyDispatcherController::class, 'dispatchWithPath'])
    ->where('controller', '[A-Za-z0-9_]+')
    ->where('action', '[A-Za-z0-9_]+')
    ->where('path', '.*')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Cloud prefix routes.
// Cake uses `/cloud/...` as a prefix that toggles session behavior;
// for now we route to the legacy controller set and port controllers incrementally.
Route::any('/cloud/{controller}/{action}', function (\Illuminate\Http\Request $request, string $controller, string $action) {
    return app(\App\Http\Controllers\Legacy\LegacyDispatcherController::class)
        ->dispatchWithPrefix($request, 'cloud', $controller, $action);
})->where('controller', '[A-Za-z0-9_]+')
  ->where('action', '[A-Za-z0-9_]+')
  ->middleware(['legacy.admin.cloud.session'])
  ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::any('/cloud/{controller}/{action}/{path}', function (\Illuminate\Http\Request $request, string $controller, string $action, string $path) {
    return app(\App\Http\Controllers\Legacy\LegacyDispatcherController::class)
        ->dispatchWithPrefixAndPath($request, 'cloud', $controller, $action, $path);
})->where('controller', '[A-Za-z0-9_]+')
  ->where('action', '[A-Za-z0-9_]+')
  ->where('path', '.*')
  ->middleware(['legacy.admin.cloud.session'])
  ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
