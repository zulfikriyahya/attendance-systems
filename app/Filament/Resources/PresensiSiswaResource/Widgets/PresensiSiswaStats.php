<?php

namespace App\Filament\Resources\PresensiSiswaResource\Widgets;

use App\Models\PresensiSiswa;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class PresensiSiswaStats extends StatsOverviewWidget
{
    protected function getCards(): array
    {
        $query = PresensiSiswa::query()
            ->whereDate('tanggal', Carbon::today());

        return [
            Card::make('Total Presensi Hari Ini', $query->count()),
            Card::make('Alfa', (clone $query)->where('statusPresensi', 'Alfa')->count()),
            Card::make('Hadir', (clone $query)->where('statusPresensi', 'Hadir')->count()),
            Card::make('Terlambat', (clone $query)->where('statusPresensi', 'Terlambat')->count()),
            Card::make('Izin', (clone $query)->where('statusPresensi', 'Izin')->count()),
            Card::make('Dispen', (clone $query)->where('statusPresensi', 'Dispen')->count()),
            Card::make('Sakit', (clone $query)->where('statusPresensi', 'Sakit')->count()),
        ];
    }
}
