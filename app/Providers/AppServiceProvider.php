<?php

namespace App\Providers;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();

        setlocale(LC_TIME, 'id_ID.utf8');
        Carbon::setLocale('id');

        FilamentColor::register([
            'primary' => Color::hex('#0f766e'),
            'gray' => Color::hex('#1e293b'),
            'info' => Color::hex('#6366f1'),
            'success' => Color::hex('#10b981'),
            'warning' => Color::hex('#f59e0b'),
            'danger' => Color::hex('#ef4444'),
        ]);

        RateLimiter::for('device-stats', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('bulk-sync', function (Request $request) {
            return Limit::perHour(20)->by($request->ip());
        });
    }
}
