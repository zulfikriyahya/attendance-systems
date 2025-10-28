<?php

namespace App\Filament\Resources\PresensiPegawaiResource\Pages;

use App\Enums\StatusPresensi;
use App\Enums\StatusPulang;
use App\Filament\Resources\PresensiPegawaiResource;
use App\Filament\Resources\PresensiPegawaiResource\Widgets\AllStatsOverview;
use App\Filament\Resources\PresensiPegawaiResource\Widgets\BulanStatsOverview;
use App\Models\PresensiPegawai;
use Filament\Actions\CreateAction;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListPresensiPegawais extends ListRecords
{
    protected static string $resource = PresensiPegawaiResource::class;

    public function getTabs(): array
    {
        $counts = PresensiPegawai::query()
            ->whereDate('tanggal', today())
            ->selectRaw('statusPresensi, COUNT(*) as jumlah')
            ->groupBy('statusPresensi')
            ->pluck('jumlah', 'statusPresensi');

        $countPulang = PresensiPegawai::query()
            ->whereDate('tanggal', today())
            ->selectRaw('statusPulang, COUNT(*) as jumlah')
            ->groupBy('statusPulang')
            ->pluck('jumlah', 'statusPulang');

        if (Auth::user()->hasRole('super_admin')) {
            return [
                // Status Presensi
                'tahun' => Tab::make('Semua')
                    ->modifyQueryUsing(
                        fn (Builder $query) => $query->whereBetween('tanggal', [now()->startOfYear(), now()->endOfYear()])
                    ),

                'bulan' => Tab::make('Bulan Ini')
                    ->modifyQueryUsing(
                        fn (Builder $query) => $query->whereBetween('tanggal', [now()->startOfMonth(), now()->endOfMonth()])
                    ),

                'hari' => Tab::make('Hari Ini')
                    ->badge(array_sum($counts->toArray()))
                    ->badgeColor('info')
                    ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('tanggal', today())),

                'hadir' => Tab::make('H')
                    ->badge($counts[StatusPresensi::Hadir->value] ?? 0)
                    ->badgeColor('success')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('statusPresensi', StatusPresensi::Hadir->value)->whereDate('tanggal', today())),

                'terlambat' => Tab::make('T')
                    ->badge($counts[StatusPresensi::Terlambat->value] ?? 0)
                    ->badgeColor('warning')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('statusPresensi', StatusPresensi::Terlambat->value)->whereDate('tanggal', today())),

                'sakit' => Tab::make('S')
                    ->badge($counts[StatusPresensi::Sakit->value] ?? 0)
                    ->badgeColor('warning')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('statusPresensi', StatusPresensi::Sakit->value)->whereDate('tanggal', today())),

                'izin' => Tab::make('I')
                    ->badge($counts[StatusPresensi::Izin->value] ?? 0)
                    ->badgeColor('warning')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('statusPresensi', StatusPresensi::Izin->value)->whereDate('tanggal', today())),

                'cuti' => Tab::make('C')
                    ->badge($counts[StatusPresensi::Cuti->value] ?? 0)
                    ->badgeColor('info')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('statusPresensi', StatusPresensi::Cuti->value)->whereDate('tanggal', today())),

                'dinasLuar' => Tab::make('DL')
                    ->badge($counts[StatusPresensi::DinasLuar->value] ?? 0)
                    ->badgeColor('warning')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('statusPresensi', StatusPresensi::DinasLuar->value)->whereDate('tanggal', today())),

                'alfa' => Tab::make('A')
                    ->badge($counts[StatusPresensi::Alfa->value] ?? 0)
                    ->badgeColor('danger')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('statusPresensi', StatusPresensi::Alfa->value)->whereDate('tanggal', today())),

                'libur' => Tab::make('L')
                    ->badge($counts[StatusPresensi::Libur->value] ?? 0)
                    ->badgeColor('gray')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('statusPresensi', StatusPresensi::Libur->value)->whereDate('tanggal', today())),

                // Status Pulang
                'pulang' => Tab::make('P')
                    ->badge($countPulang[StatusPulang::Pulang->value] ?? 0)
                    ->badgeColor('success')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('statusPulang', StatusPulang::Pulang->value)->whereDate('tanggal', today())),

                'pulangCepat' => Tab::make('PSW')
                    ->badge($countPulang[StatusPulang::PulangCepat->value] ?? 0)
                    ->badgeColor('warning')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('statusPulang', StatusPulang::PulangCepat->value)->whereDate('tanggal', today())),

                'mangkir' => Tab::make('M')
                    ->badge($countPulang[StatusPulang::Mangkir->value] ?? 0)
                    ->badgeColor('danger')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('statusPulang', StatusPulang::Mangkir->value)->whereDate('tanggal', today())),
            ];
        }

        return [];
    }

    protected function getHeaderWidgets(): array
    {
        if (! Auth::user()->hasRole('super_admin')) {
            return [
                AllStatsOverview::class,
                BulanStatsOverview::class,
            ];
        }

        return [];
    }

    protected function getHeaderActions(): array
    {
        if (Auth::user()->hasRole('super_admin')) {
            return [
                CreateAction::make()
                    ->label('Create')
                    ->color(Color::Green)
                    ->size('sm')
                    ->icon('heroicon-o-plus-circle')
                    ->outlined(),
            ];
        }

        return [];
    }
}
