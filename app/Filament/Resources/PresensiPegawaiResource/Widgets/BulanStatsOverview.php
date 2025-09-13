<?php

namespace App\Filament\Resources\PresensiPegawaiResource\Widgets;

use Carbon\Carbon;
use App\Models\PresensiPegawai;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class BulanStatsOverview extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getHeading(): ?string
    {
        $bulan = now()->format('F Y');
        return 'Statistik Presensi Bulan ' . $bulan;
    }
    protected function getStats(): array
    {

        $pegawaiId = Auth::user()?->pegawai?->id;
        return [
            Stat::make(
                'Status Hadir',
                PresensiPegawai::query()
                    ->where('statusPresensi', 'Hadir')
                    ->when($pegawaiId, fn($query) => $query->where('pegawai_id', $pegawaiId))
                    ->whereMonth('tanggal', Carbon::now()->month)
                    ->whereYear('tanggal', Carbon::now()->year)
                    ->count() . ' Hari'
            )
                ->chartColor(Color::Green)
                ->chart([7, 2, 10, 3, 15, 4, 10])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => "\$dispatch('setStatusFilter', { filter: 'Hadir' })",
                ]),

            Stat::make(
                'Status Terlambat',
                PresensiPegawai::query()
                    ->where('statusPresensi', 'Terlambat')
                    ->when($pegawaiId, fn($query) => $query->where('pegawai_id', $pegawaiId))
                    ->whereMonth('tanggal', Carbon::now()->month)
                    ->whereYear('tanggal', Carbon::now()->year)
                    ->count() . ' Hari'
            )
                ->chartColor(Color::Amber)
                ->chart([10, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => "\$dispatch('setStatusFilter', { filter: 'Terlambat' })",
                ]),

            Stat::make(
                'Status Sakit, Izin, Dispensasi',
                PresensiPegawai::query()
                    ->whereIn('statusPresensi', ['Sakit', 'Izin', 'Dispen'])
                    ->when($pegawaiId, fn($query) => $query->where('pegawai_id', $pegawaiId))
                    ->whereMonth('tanggal', Carbon::now()->month)
                    ->whereYear('tanggal', Carbon::now()->year)
                    ->count() . ' Hari'
            )
                ->chartColor(Color::Fuchsia)
                ->chart([10, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => "\$dispatch('setStatusFilter', { filter: ['Sakit','Izin','Dispen'] })",
                ]),

            Stat::make(
                'Status Alfa',
                PresensiPegawai::query()
                    ->where('statusPresensi', 'Alfa')
                    ->when($pegawaiId, fn($query) => $query->where('pegawai_id', $pegawaiId))
                    ->whereMonth('tanggal', Carbon::now()->month)
                    ->whereYear('tanggal', Carbon::now()->year)
                    ->count() . ' Hari'
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
