<?php

namespace App\Filament\Resources\PengajuanKartuResource\Pages;

use App\Filament\Resources\PengajuanKartuResource;
use App\Filament\Resources\PengajuanKartuResource\Widgets\StatsOverview;
use App\Models\PengajuanKartu;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;

class ListPengajuanKartus extends ListRecords
{
    protected static string $resource = PengajuanKartuResource::class;

    protected function getHeaderWidgets(): array
    {
        if (Auth::user()->hasRole('super_admin')) {
            return [
                StatsOverview::class,
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

                Action::make('cetak')
                    ->label('Print')
                    ->color(Color::Blue)
                    ->size('sm')
                    ->icon('heroicon-o-printer')
                    ->outlined()
                    ->url(fn (): string => route('cetak-kartu'))
                    ->openUrlInNewTab()
                    ->visible(PengajuanKartu::where('status', 'Proses')->count() >= 10), // Tampilkan tombol jika status Proses lebih, atau sama dengan 10.
            ];
        }

        return [];
    }
}
