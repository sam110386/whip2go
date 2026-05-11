<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Legacy\HomesController;
use App\Http\Controllers\Legacy\PagesController;
use App\Http\Controllers\Legacy\LegacyDispatcherController;
use App\Http\Middleware\VerifyCsrfToken;

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
Route::get('/', fn() => redirect('/logins/index'));

// Cake static aliases
Route::match(['get', 'post'], '/aboutus', [HomesController::class, 'driveitawayaboutus']);
Route::match(['get', 'post'], '/contactus', [HomesController::class, 'contactus']);
Route::match(['get', 'post'], '/drivers', [HomesController::class, 'driveitawaydrivers']);
Route::match(['get', 'post'], '/dealers', [HomesController::class, 'driveitawaydealers']);
Route::match(['get', 'post'], '/featured', [HomesController::class, 'featured']);
Route::match(['get', 'post'], '/driveitaway', [HomesController::class, 'driveitaway']);
Route::match(['get', 'post'], '/press-kit-facts-about-driveItAway', [HomesController::class, 'press_kit_facts_about_driveItAway']);
Route::match(['get', 'post'], '/leadership-and-company-mission', [HomesController::class, 'leadership_and_company_mission']);
Route::match(['get', 'post'], '/publications-blog-industry-videos', [HomesController::class, 'publications_blog_industry_videos']);
Route::match(['get', 'post'], '/event-industry-presentation', [HomesController::class, 'event_industry_presentation']);
Route::match(['get', 'post'], '/press-releases-and-news', [HomesController::class, 'press_releases_and_news']);
Route::match(['get', 'post'], '/app-terms', [HomesController::class, 'terms']);
Route::match(['get', 'post'], '/app-privacy-policy', [HomesController::class, 'privacy']);
Route::match(['get', 'post'], '/nada2019', [HomesController::class, 'nada'])->name('legacy.nada');
Route::match(['get', 'post'], '/mobile-faq', [PagesController::class, 'mobilefaq']);
Route::match(['get', 'post'], '/telematics', [PagesController::class, 'telematics']);

Route::get('/admin', fn() => redirect('/admin/admins/login'));
Route::get('/admin/admins', fn() => redirect('/admin/admins/login'));

// Cake redirect parity
Route::redirect('/pages', 'https://www.driveitaway.com', 301);
Route::redirect('/pages/{path}', 'https://www.driveitaway.com', 301)->where('path', '.*');
Route::redirect('/CsV2Services/{path}', '/logins/index', 301)->where('path', '.*');
Route::redirect('/cs_v2_services/{path}', '/logins/index', 301)->where('path', '.*');
Route::redirect('/cloud/linked_bookings/create', '/cloud/linked_bookings/index', 301);
Route::redirect('/cloud/linked_bookings/createbooking', '/cloud/linked_bookings/index', 301);
Route::redirect('/admin/bookings/create', '/admin/bookings/index', 301);
Route::redirect('/admin/bookings/createbooking', '/admin/bookings/index', 301);
Route::redirect('/logins/forgotpassword', '/logins/forgotPassword', 301);
Route::redirect('/telematics_subscriptions/page', '/telematics', 301);

// Cake redirect parity (used after login)
Route::get('/users/dashboard', fn() => redirect('/dashboard/index', 301));

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
// Admin › Report module — dynamic dispatcher routes.
// Mirrors the /admin/{controller}/{action} pattern but for the Report sub-namespace:
//   /admin/report/{controller}/{action}        → Admin\Report\{Studly}Controller
//   /admin/report/{controller}/{action}/{path} → same, with extra path params
// --------------------------------------------------------------------------
Route::any('/admin/report/{controller}/{action}', [LegacyDispatcherController::class, 'dispatchAdminReport'])
    ->where('controller', '[A-Za-z0-9_]+')
    ->where('action', '[A-Za-z0-9_]+')
    ->middleware(['legacy.admin.session'])
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::any('/admin/report/{controller}/{action}/{path}', [LegacyDispatcherController::class, 'dispatchAdminReportWithPath'])
    ->where('controller', '[A-Za-z0-9_]+')
    ->where('action', '[A-Za-z0-9_]+')
    ->where('path', '.*')
    ->middleware(['legacy.admin.session'])
    ->withoutMiddleware([VerifyCsrfToken::class]);

// --------------------------------------------------------------------------
// Cake-like dispatcher routes (incremental controller porting).
// Supports only 2-segment routes for now:
// - /{controller}/{action}
// - /admin/{controller}/{action}
// --------------------------------------------------------------------------
Route::any('/admin/{controller}/{action}', [LegacyDispatcherController::class, 'dispatchAdmin'])
    ->where('controller', '[A-Za-z0-9_]+')
    ->where('action', '[A-Za-z0-9_]+')
    ->middleware(['legacy.admin.session'])
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::any('/admin/{controller}/{action}/{path}', [LegacyDispatcherController::class, 'dispatchAdminWithPath'])
    ->where('controller', '[A-Za-z0-9_]+')
    ->where('action', '[A-Za-z0-9_]+')
    ->where('path', '.*')
    ->middleware(['legacy.admin.session'])
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::any('/{controller}/{action}', [LegacyDispatcherController::class, 'dispatch'])
    ->where('controller', '[A-Za-z0-9_]+')
    ->where('action', '[A-Za-z0-9_]+')
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::any('/{controller}/{action}/{path}', [LegacyDispatcherController::class, 'dispatchWithPath'])
    ->where('controller', '[A-Za-z0-9_]+')
    ->where('action', '[A-Za-z0-9_]+')
    ->where('path', '.*')
    ->withoutMiddleware([VerifyCsrfToken::class]);

// Cloud prefix routes.
// Cake uses `/cloud/...` as a prefix that toggles session behavior;
// for now we route to the legacy controller set and port controllers incrementally.
Route::any('/cloud/{controller}/{action}', function (\Illuminate\Http\Request $request, string $controller, string $action) {
    return app(LegacyDispatcherController::class)
        ->dispatchWithPrefix($request, 'cloud', $controller, $action);
})->where('controller', '[A-Za-z0-9_]+')
    ->where('action', '[A-Za-z0-9_]+')
    ->middleware(['legacy.admin.cloud.session'])
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::any('/cloud/{controller}/{action}/{path}', function (\Illuminate\Http\Request $request, string $controller, string $action, string $path) {
    return app(LegacyDispatcherController::class)
        ->dispatchWithPrefixAndPath($request, 'cloud', $controller, $action, $path);
})->where('controller', '[A-Za-z0-9_]+')
    ->where('action', '[A-Za-z0-9_]+')
    ->where('path', '.*')
    ->middleware(['legacy.admin.cloud.session'])
    ->withoutMiddleware([VerifyCsrfToken::class]);
