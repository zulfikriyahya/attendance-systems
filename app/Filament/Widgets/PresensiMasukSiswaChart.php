<?php

namespace App\Filament\Widgets;

use Flowframe\Trend\Trend;
use App\Enums\StatusPresensi;
use Illuminate\Support\Carbon;
use Flowframe\Trend\TrendValue;
use Filament\Widgets\ChartWidget;
use App\Models\PresensiSiswa as PresensiSiswaModel;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class PresensiMasukSiswaChart extends ChartWidget
{
    use HasWidgetShield;

    protected static ?string $maxHeight = '180px';

    protected static ?string $heading = 'Statistik Presensi Masuk Siswa';

    protected function getData(): array
    {
        $labels = [];
        $datasets = [];
        $colorMap = [
            'Hadir' => '#22c55e',      // green-500
            'Terlambat' => '#f97316',  // orange-500
            'Izin' => '#facc15',       // yellow-400
            'Sakit' => '#3b82f6',      // blue-500
            'Alfa' => '#ef4444',       // red-500
            'Cuti' => '#ec4899',       // pink-500
            'DinasLuar' => '#8b5cf6',  // violet-500 (ungu)
            'Libur' => '#9ca3af',      // gray-500 (abu-abu)
            'Dispen' => '#3b81c9',
        ];

        foreach (StatusPresensi::cases() as $status) {
            $data = Trend::query(
                PresensiSiswaModel::query()->where('statusPresensi', $status->value)
            )
                ->dateColumn('tanggal')
                ->between(
                    start: now()->startOfYear(),
                    end: now()->endOfYear(),
                )
                ->perMonth()
                ->count();

            // Ambil label sekali saja
            if (empty($labels)) {
                $labels = $data->map(
                    fn(TrendValue $value) => Carbon::parse($value->date)->translatedFormat('F Y')
                )->toArray();
            }

            $datasets[] = [
                'label' => $status->name,
                'data' => $data->map(fn(TrendValue $value) => $value->aggregate)->toArray(),
                'borderColor' => $colorMap[$status->name] ?? '#9ca3af',
                'fill' => false, // tidak ada warna area
                'tension' => 0.3, // garis lebih halus
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
