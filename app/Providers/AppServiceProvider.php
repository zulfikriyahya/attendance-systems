<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Filament\Navigation\NavigationGroup;
use Illuminate\Cache\RateLimiting\Limit;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }
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
