<?php

namespace App\Filament\Resources\PresensiPegawaiResource\Widgets;

use App\Models\PresensiPegawai;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class PresensiPegawaiStats extends StatsOverviewWidget
{
    protected function getCards(): array
    {
        $query = PresensiPegawai::query()
            ->whereDate('tanggal', Carbon::today());

        return [
            Card::make('Total Presensi Hari Ini', $query->count()),
            Card::make('Alfa', (clone $query)->where('statusPresensi', 'Alfa')->count()),
            Card::make('Hadir', (clone $query)->where('statusPresensi', 'Hadir')->count()),
            Card::make('Terlambat', (clone $query)->where('statusPresensi', 'Terlambat')->count()),
            Card::make('Izin', (clone $query)->where('statusPresensi', 'Izin')->count()),
            Card::make('Sakit', (clone $query)->where('statusPresensi', 'Sakit')->count()),
            // Card::make('Cuti', (clone $query)->where('statusPresensi', 'Cuti')->count()),
            // Card::make('Dinas Luar', (clone $query)->where('statusPresensi', 'Dinas Luar')->count()),
        ];
    }
}
