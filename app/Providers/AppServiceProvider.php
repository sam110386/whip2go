<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Observers\VehicleObserver;
use App\Observers\CsVehicleIssueObserver;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\CsVehicleIssue;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $helpers = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'legacy_helpers.php';
        if (is_file($helpers)) {
            require_once $helpers;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CsVehicleIssue::observe(CsVehicleIssueObserver::class);
        Vehicle::observe(VehicleObserver::class);

    }
}
