<?php

namespace App\Filament\Resources\PresensiSiswaResource\Widgets;

use App\Models\PresensiSiswa;
use Carbon\Carbon;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class BulanStatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getHeading(): ?string
    {
        $bulan = now()->format('F Y');

        return 'Statistik Presensi Bulan '.$bulan;
    }

    protected function getStats(): array
    {

        $siswaId = Auth::user()?->siswa?->id;

        return [
            Stat::make(
                'Status Hadir',
                PresensiSiswa::query()
                    ->where('statusPresensi', 'Hadir')
                    ->when($siswaId, fn ($query) => $query->where('siswa_id', $siswaId))
                    ->whereMonth('tanggal', Carbon::now()->month)
                    ->whereYear('tanggal', Carbon::now()->year)
                    ->count().' Hari'
            )
                ->chartColor(Color::Green)
                ->chart([7, 2, 10, 3, 15, 4, 10])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => "\$dispatch('setStatusFilter', { filter: 'Hadir' })",
                ]),

            Stat::make(
                'Status Terlambat',
                PresensiSiswa::query()
                    ->where('statusPresensi', 'Terlambat')
                    ->when($siswaId, fn ($query) => $query->where('siswa_id', $siswaId))
                    ->whereMonth('tanggal', Carbon::now()->month)
                    ->whereYear('tanggal', Carbon::now()->year)
                    ->count().' Hari'
            )
                ->chartColor(Color::Amber)
                ->chart([10, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => "\$dispatch('setStatusFilter', { filter: 'Terlambat' })",
                ]),

            Stat::make(
                'Status Sakit, Izin, Dispensasi',
                PresensiSiswa::query()
                    ->whereIn('statusPresensi', ['Sakit', 'Izin', 'Dispen'])
                    ->when($siswaId, fn ($query) => $query->where('siswa_id', $siswaId))
                    ->whereMonth('tanggal', Carbon::now()->month)
                    ->whereYear('tanggal', Carbon::now()->year)
                    ->count().' Hari'
            )
                ->chartColor(Color::Fuchsia)
                ->chart([10, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    // kalau filter array, sebaiknya pakai JSON biar jelas
                    'wire:click' => "\$dispatch('setStatusFilter', { filter: ['Sakit','Izin','Dispen'] })",
                ]),

            Stat::make(
                'Status Alfa',
                PresensiSiswa::query()
                    ->where('statusPresensi', 'Alfa')
                    ->when($siswaId, fn ($query) => $query->where('siswa_id', $siswaId))
                    ->whereMonth('tanggal', Carbon::now()->month)
                    ->whereYear('tanggal', Carbon::now()->year)
                    ->count().' Hari'
            )
                ->chartColor(Color::Red)
                ->chart([7, 2, 10, 3, 15, 4, 10])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => "\$dispatch('setStatusFilter', { filter: 'Alfa' })",
                ]),
        ];
    }
}
