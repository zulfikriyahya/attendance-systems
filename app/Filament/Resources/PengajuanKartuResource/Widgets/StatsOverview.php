<?php

namespace App\Filament\Resources\PengajuanKartuResource\Widgets;

use App\Models\PengajuanKartu;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class StatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [
            Stat::make('Pengajuan Kartu', PengajuanKartu::where('status', 'Pending')->count())
                ->chartColor(Color::Orange)
                ->description('Jumlah kartu yang masih menunggu persetujuan.')
                ->chart([100, 40, 120, 60, 150, 80, 130]),

            Stat::make('Kartu Diproses', PengajuanKartu::where('status', 'Proses')->count())
                ->chartColor(Color::Violet)
                ->description('Kartu yang sedang dalam tahap percetakan atau verifikasi.')
                ->chart([10, 2, 7, 3, 15, 4, 10]),

            Stat::make('Kartu Selesai', PengajuanKartu::where('status', 'Selesai')->count())
                ->chartColor(Color::Green)
                ->description('Kartu yang sudah selesai dan siap diambil.')
                ->chart([100, 40, 120, 60, 150, 80, 130]),
            Stat::make('Total Denda', 'Rp. '.number_format(PengajuanKartu::where('statusAmbil', true)->sum('biaya'), 0, ',', '.').',-')
                ->chartColor(Color::Cyan)
                ->description(new HtmlString('<span class="text-md"><b>Untuk keperluan Percetakan Kartu.</b></span><br><span class="text-xs">Selebihnya akan disalurkan sebagai <b>sedekah</b>.</span>'))
                ->chart([10, 2, 7, 3, 15, 4, 10]),
        ];
    }
}
