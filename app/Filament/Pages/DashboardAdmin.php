<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class DashboardAdmin extends BaseDashboard
{
    use HasPageShield;

    protected function getShieldRedirectPath(): string
    {
        return url()->previous();
    }

    protected static ?string $navigationIcon = 'heroicon-o-signal';
}
