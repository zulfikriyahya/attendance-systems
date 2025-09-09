<?php

namespace App\Filament\Widgets;

use App\Enums\StatusPulang;
use App\Models\PresensiSiswa as PresensiSiswaModel;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;

class PresensiPulangSiswaChart extends ChartWidget
{
    use HasWidgetShield;

    protected function getExtraAttributes(): array
    {
        return [
            'style' => 'max-height: 75%;', // atau sesuaikan tinggi sesuai kebutuhan
        ];
    }

    protected static ?string $heading = 'Statistik Presensi Pulang Siswa';

    protected function getData(): array
    {
        $labels = [];
        $datasets = [];
        $colorMap = [
            'Pulang' => '#22c55e',  // green-500
            'PulangCepat' => '#f97316',  // orange-500
            'Mangkir' => '#ef4444',  // red-500
        ];

        foreach (StatusPulang::cases() as $status) {
            $data = Trend::query(
                PresensiSiswaModel::query()->where('statusPulang', $status->value)
            )
                ->dateColumn('tanggal')
                ->between(
                    start: now()->startOfYear(),
                    end: now()->endOfYear(),
                )
                ->perMonth()
                ->count();

            // Ambil label sekali
            if (empty($labels)) {
                $labels = $data->map(
                    fn (TrendValue $value) => Carbon::parse($value->date)->translatedFormat('F Y')
                )->toArray();
            }

            $datasets[] = [
                'label' => $status->name,
                'data' => $data->map(fn (TrendValue $value) => $value->aggregate)->toArray(),
                'backgroundColor' => $colorMap[$status->name] ?? '#9ca3af', // default abu-abu
                'borderColor' => '#cce6e6',
                'borderWidth' => 1,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line'; // atau 'line'
    }
}
