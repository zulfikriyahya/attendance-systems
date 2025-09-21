<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Pengguna', User::all()->count())
                ->chartColor(Color::Emerald)
                ->chart([7, 2, 10, 3, 15, 4, 10]),
            Stat::make('Pengguna Aktif', User::where('status', true)->count())
                ->chartColor(Color::Emerald)
                ->chart([7, 2, 10, 3, 15, 4, 10]),
            Stat::make('Pengguna Non Aktif', User::where('status', false)->count())
                ->chartColor(Color::Rose)
                ->chart([10, 2, 7, 3, 15, 4, 17]),
            Stat::make('Upload Avatar', User::whereNotNull('avatar')->count())
                ->chartColor(Color::Cyan)
                ->chart([7, 2, 10, 3, 15, 4, 10]),
        ];
    }
}
