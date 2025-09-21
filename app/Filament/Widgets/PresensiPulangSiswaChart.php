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

    protected static ?string $maxHeight = '180px';

    protected static ?string $heading = 'Statistik Presensi Pulang Siswa';

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
                PresensiSiswaModel::query()->where('statusPulang', $status->value)
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
                    fn (TrendValue $value) => Carbon::parse($value->date)->translatedFormat('F Y')
                )->toArray();
            }

            $datasets[] = [
                'label' => $status->name,
                'data' => $data->map(fn (TrendValue $value) => $value->aggregate)->toArray(),
                'borderColor' => $colorMap[$status->name] ?? '#9ca3af',
                'fill' => false, // tidak diisi warna
                'tension' => 0.3, // bikin garis agak melengkung (opsional)
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
