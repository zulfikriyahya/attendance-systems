<?php

namespace App\Filament\Widgets;

use Flowframe\Trend\Trend;
use App\Enums\StatusPulang;
use Illuminate\Support\Carbon;
use Flowframe\Trend\TrendValue;
use Filament\Widgets\ChartWidget;
use App\Models\PresensiPegawai as PresensiPegawaiModel;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class PresensiPulangPegawaiChart extends ChartWidget
{
    use HasWidgetShield;

    protected static ?string $maxHeight = '180px';

    protected static ?string $heading = 'Statistik Presensi Pulang Pegawai';

    protected function getData(): array
    {
        $labels = [];
        $datasets = [];
        $colorMap = [
            'Pulang' => '#22c55e',     // green-500
            'PulangCepat' => '#f97316', // orange-500
            'Mangkir' => '#ef4444',    // red-500
        ];

        foreach (StatusPulang::cases() as $status) {
            $data = Trend::query(
                PresensiPegawaiModel::query()->where('statusPulang', $status->value)
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
                'fill' => false, // jangan diisi background
                'tension' => 0.3, // biar garis smooth
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
