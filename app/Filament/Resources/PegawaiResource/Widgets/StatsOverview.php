<?php

namespace App\Filament\Resources\PegawaiResource\Widgets;

use App\Models\Pegawai;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [
            Stat::make('Pegawai Aktif', Pegawai::where('status', true)->count())
                ->chartColor(Color::Cyan)
                ->chart([7, 2, 10, 3, 15, 4, 10]),
            Stat::make('Pegawai Non Aktif', Pegawai::where('status', false)->count())
                ->chartColor(Color::Fuchsia)
                ->chart([10, 2, 7, 3, 15, 4, 17]),
            Stat::make('Pegawai Pria', Pegawai::where('status', true)->where('jenisKelamin', 'Pria')->count())
                ->chartColor(Color::Cyan)
                ->chart([7, 2, 10, 3, 15, 4, 10]),
            Stat::make('Pegawai Wanita', Pegawai::where('status', true)->where('jenisKelamin', 'Wanita')->count())
                ->chartColor(Color::Fuchsia)
                ->chart([10, 2, 7, 3, 15, 4, 17]),
            Stat::make('Pegawai Guru', Pegawai::where('status', true)->whereHas('jabatan', fn ($q) => $q->where('nama', 'Guru'))->count())
                ->chartColor(Color::Cyan)
                ->chart([7, 2, 10, 3, 15, 4, 10]),
            Stat::make('Pegawai Staf', Pegawai::where('status', true)->whereHas('jabatan', fn ($q) => $q->where('nama', 'Staf'))->count())
                ->chartColor(Color::Fuchsia)
                ->chart([10, 2, 7, 3, 15, 4, 17]),
        ];
    }
}
