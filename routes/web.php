<?php

use App\Http\Controllers\Legacy\HomesController;
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

// Cake single-segment marketing URLs (`app/Config/routes.php`) — must register before `/{controller}/{action}`.
Route::match(['get', 'post'], '/contactus', [HomesController::class, 'contactus']);
Route::match(['get', 'post'], '/nada2019', [HomesController::class, 'nada']);
Route::get('/aboutus', [HomesController::class, 'driveitawayaboutus']);
Route::get('/drivers', [HomesController::class, 'driveitawaydrivers']);
Route::get('/dealers', [HomesController::class, 'driveitawaydealers']);
Route::get('/featured', [HomesController::class, 'featured']);
Route::get('/driveitaway', [HomesController::class, 'driveitaway']);
Route::get('/press-kit-facts-about-driveItAway', [HomesController::class, 'press_kit_facts_about_driveItAway']);
Route::get('/leadership-and-company-mission', [HomesController::class, 'leadership_and_company_mission']);
Route::get('/publications-blog-industry-videos', [HomesController::class, 'publications_blog_industry_videos']);
Route::get('/event-industry-presentation', [HomesController::class, 'event_industry_presentation']);
Route::get('/press-releases-and-news', [HomesController::class, 'press_releases_and_news']);
Route::get('/app-terms', [HomesController::class, 'terms']);
Route::get('/app-privacy-policy', [HomesController::class, 'privacy']);

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
