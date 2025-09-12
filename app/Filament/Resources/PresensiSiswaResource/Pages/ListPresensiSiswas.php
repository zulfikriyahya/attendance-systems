<?php

namespace App\Filament\Resources\PresensiSiswaResource\Pages;

use App\Enums\StatusPulang;
use App\Enums\StatusPresensi;
use App\Models\PresensiSiswa;
use Filament\Actions\CreateAction;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PresensiSiswaResource;
use App\Filament\Resources\PresensiSiswaResource\Widgets\PresensiSiswaStats;

class ListPresensiSiswas extends ListRecords
{
    protected static string $resource = PresensiSiswaResource::class;

    public function getTabs(): array
    {
        $counts = PresensiSiswa::query()
            ->whereDate('tanggal', today())
            ->selectRaw('statusPresensi, COUNT(*) as jumlah')
            ->groupBy('statusPresensi')
            ->pluck('jumlah', 'statusPresensi');

        $countPulang = PresensiSiswa::query()
            ->whereDate('tanggal', today())
            ->selectRaw('statusPulang, COUNT(*) as jumlah')
            ->groupBy('statusPulang')
            ->pluck('jumlah', 'statusPulang');

        return [
            // Status Presensi
            'tahun' => Tab::make('Semua')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->whereBetween('tanggal', [now()->startOfYear(), now()->endOfYear()])
                ),

            'bulan' => Tab::make('Bulan Ini')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->whereBetween('tanggal', [now()->startOfMonth(), now()->endOfMonth()])
                ),

            'hari' => Tab::make('Hari Ini')
                ->badge(array_sum($counts->toArray()))
                ->badgeColor('info')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereDate('tanggal', today())),

            'hadir' => Tab::make('H')
                ->badge($counts[StatusPresensi::Hadir->value] ?? 0)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('statusPresensi', StatusPresensi::Hadir->value)->whereDate('tanggal', today())),

            'terlambat' => Tab::make('T')
                ->badge($counts[StatusPresensi::Terlambat->value] ?? 0)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('statusPresensi', StatusPresensi::Terlambat->value)->whereDate('tanggal', today())),

            'sakit' => Tab::make('S')
                ->badge($counts[StatusPresensi::Sakit->value] ?? 0)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('statusPresensi', StatusPresensi::Sakit->value)->whereDate('tanggal', today())),

            'izin' => Tab::make('I')
                ->badge($counts[StatusPresensi::Izin->value] ?? 0)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('statusPresensi', StatusPresensi::Izin->value)->whereDate('tanggal', today())),

            'dispen' => Tab::make('D')
                ->badge($counts[StatusPresensi::Dispen->value] ?? 0)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('statusPresensi', StatusPresensi::Dispen->value)->whereDate('tanggal', today())),

            'alfa' => Tab::make('A')
                ->badge($counts[StatusPresensi::Alfa->value] ?? 0)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('statusPresensi', StatusPresensi::Alfa->value)->whereDate('tanggal', today())),

            'libur' => Tab::make('L')
                ->badge($counts[StatusPresensi::Libur->value] ?? 0)
                ->badgeColor('gray')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('statusPresensi', StatusPresensi::Libur->value)->whereDate('tanggal', today())),

            // Status Pulang
            'pulang' => Tab::make('P')
                ->badge($countPulang[StatusPulang::Pulang->value] ?? 0)
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('statusPulang', StatusPulang::Pulang->value)->whereDate('tanggal', today())),

            'pulangCepat' => Tab::make('PSW')
                ->badge($countPulang[StatusPulang::PulangCepat->value] ?? 0)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('statusPulang', StatusPulang::PulangCepat->value)->whereDate('tanggal', today())),

            'bolos' => Tab::make('B')
                ->badge($countPulang[StatusPulang::Bolos->value] ?? 0)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('statusPulang', StatusPulang::Bolos->value)->whereDate('tanggal', today())),
        ];
    }

    protected function getHeaderActions(): array
    {
        if (Auth::user()->hasRole('super_admin')) {
            return [
                CreateAction::make()
                    ->label('Tambah Presensi')
                    ->outlined()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Emerald),
            ];
        }
        return [];
    }
}
