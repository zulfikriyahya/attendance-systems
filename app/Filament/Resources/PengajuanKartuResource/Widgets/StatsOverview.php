<?php

namespace App\Filament\Resources\PengajuanKartuResource\Widgets;

use App\Models\PengajuanKartu;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [
            Stat::make('Pengajuan Kartu', PengajuanKartu::where('status', 'Pending')->count())
                ->chartColor(Color::Orange)
                ->chart([7, 2, 10, 3, 15, 4, 10]),
            Stat::make('Kartu Diproses', PengajuanKartu::where('status', 'Proses')->count())
                ->chartColor(Color::Violet)
                ->chart([10, 2, 7, 3, 15, 4, 10]),
            Stat::make('Kartu Selesai', PengajuanKartu::where('status', 'Selesai')->count())
                ->chartColor(Color::Green)
                ->chart([7, 2, 10, 3, 15, 4, 10]),
            Stat::make('Total Denda', 'Rp. ' . number_format(PengajuanKartu::where('statusAmbil', true)->sum('biaya'), 0, ',', '.') . ',-')
                ->chartColor(Color::Red)
                ->description('Sebagian dana akan dihibahkan untuk pembangunan Masjid.')
                ->chart([10, 2, 7, 3, 15, 4, 10]),
        ];
    }
}
