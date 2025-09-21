<?php

namespace App\Filament\Resources\InformasiResource\Widgets;

use App\Models\Informasi;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [
            Stat::make('Status Draft', Informasi::where('status', 'Draft')->count())
                ->chartColor(Color::Orange)
                ->chart([7, 2, 10, 3, 15, 4, 10]),
            Stat::make('Status Publish', Informasi::where('status', 'Publish')->count())
                ->chartColor(Color::Cyan)
                ->chart([10, 2, 7, 3, 15, 4, 17]),
            Stat::make('Status Archive', Informasi::where('status', 'Archive')->count())
                ->chartColor(Color::Zinc)
                ->chart([7, 2, 10, 3, 15, 4, 10]),
        ];
    }
}
