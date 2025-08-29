<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Dashboard as BaseDashboard;

class DashboardAdmin extends BaseDashboard
{
    use HasPageShield;

    protected function getShieldRedirectPath(): string
    {
        return url()->previous();
    }

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
}
