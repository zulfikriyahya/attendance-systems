<?php

namespace App\Filament\Resources\SiswaResource\Widgets;

use App\Models\Siswa;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [
            Stat::make('Siswa Aktif', Siswa::where('status', true)->count())
                ->chartColor(Color::Cyan)
                ->chart([7, 2, 10, 3, 15, 4, 10]),
            Stat::make('Siswa Non Aktif', Siswa::where('status', false)->count())
                ->chartColor(Color::Fuchsia)
                ->chart([10, 2, 7, 3, 15, 4, 17]),
            Stat::make('Siswa Laki-laki', Siswa::where('status', true)->where('jenisKelamin', 'Pria')->count())
                ->chartColor(Color::Cyan)
                ->chart([7, 2, 10, 3, 15, 4, 10]),
            Stat::make('Siswa Perempuan', Siswa::where('status', true)->where('jenisKelamin', 'Wanita')->count())
                ->chartColor(Color::Fuchsia)
                ->chart([10, 2, 7, 3, 15, 4, 17]),
            Stat::make('Kelas Unggulan', Siswa::where('status', true)->whereHas('jabatan', fn ($q) => $q->where('nama', 'Siswa Unggulan'))->count())
                ->chartColor(Color::Cyan)
                ->chart([7, 2, 10, 3, 15, 4, 10]),
            Stat::make('Kelas Reguler', Siswa::where('status', true)->whereHas('jabatan', fn ($q) => $q->where('nama', 'Siswa Reguler'))->count())
                ->chartColor(Color::Fuchsia)
                ->chart([10, 2, 7, 3, 15, 4, 17]),
        ];
    }
}
