<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            // Avoid nullsafe operator for older IDE parsers.
            $user = $request->user();
            return Limit::perMinute(60)->by(($user && isset($user->id)) ? $user->id : $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Legacy CakePHP-compatible routes (no `/api` URL prefix).
            Route::middleware('api')
                ->group(base_path('routes/legacy-api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
