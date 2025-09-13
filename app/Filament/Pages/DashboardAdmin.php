<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\PresensiMasukSiswaChart;
use App\Filament\Widgets\PresensiPulangSiswaChart;
use App\Filament\Widgets\PresensiMasukPegawaiChart;
use App\Filament\Widgets\PresensiPulangPegawaiChart;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use App\Filament\Resources\PengajuanKartuResource\Widgets\StatsOverview as KartuStats;

class DashboardAdmin extends BaseDashboard
{
    use HasPageShield;

    protected function getShieldRedirectPath(): string
    {
        return url()->previous();
    }

    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected function formatDisplayName(string|null $name): string
    {
        if (! $name) {
            return '';
        }

        // Pisah berdasarkan koma, simpan sisanya apa adanya
        $parts = array_map('trim', explode(',', $name));

        // Jika ada setidaknya satu bagian, ubah bagian pertama jadi Title Case
        if (count($parts) > 0 && $parts[0] !== '') {
            $parts[0] = mb_convert_case(mb_strtolower($parts[0]), MB_CASE_TITLE, 'UTF-8');
        }

        // Gabungkan kembali dengan koma + spasi
        return implode(', ', $parts);
    }

    public function getTitle(): HtmlString|string
    {
        $hour = Carbon::now()->format('H');

        if ($hour >= 5 && $hour < 11) {
            $greeting = '<svg class="w-5 h-5 inline text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8.66-13.66l-.7.7M4.05 19.95l-.7.7M21 12h1M2 12H1m16.95 7.95l.7.7M4.05 4.05l.7.7M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg> Selamat Pagi';
        } elseif ($hour >= 11 && $hour < 15) {
            $greeting = '<svg class="w-5 h-5 inline text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8.66-13.66l-.7.7M4.05 19.95l-.7.7M21 12h1M2 12H1m16.95 7.95l.7.7M4.05 4.05l.7.7M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg> Selamat Siang';
        } elseif ($hour >= 15 && $hour < 18) {
            $greeting = '<svg class="w-5 h-5 inline text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m8.66-13.66l-.7.7M4.05 19.95l-.7.7M21 12h1M2 12H1m16.95 7.95l.7.7M4.05 4.05l.7.7M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg> Selamat Sore';
        } else {
            $greeting = '<svg class="w-5 h-5 inline text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z"/></svg> Selamat Malam';
        }

        $displayName = $this->formatDisplayName(Auth::user()?->name ?? '');

        return new HtmlString($greeting . ', ' . e($displayName).'!');
    }

    public function widgets(): array
    {
        return [
            KartuStats::class,
            PresensiMasukPegawaiChart::class,
            PresensiPulangPegawaiChart::class,
            PresensiMasukSiswaChart::class,
            PresensiPulangSiswaChart::class,
        ];
    }

    public function getHeaderWidgets(): array
    {
        
        return [
            KartuStats::class,
        ];
    }
}
