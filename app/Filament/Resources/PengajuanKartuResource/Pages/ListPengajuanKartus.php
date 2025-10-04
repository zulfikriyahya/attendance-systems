<?php

namespace App\Filament\Resources\PengajuanKartuResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\PengajuanKartuResource;
use App\Filament\Resources\PengajuanKartuResource\Widgets\StatsOverview;

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
                    ->label('Buat Pengajuan')
                    ->outlined()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Emerald),
                    // TODO: Add Print Action
                Action::make('cetak')
                    ->label('Cetak Kartu')
                    ->outlined()
                    ->icon('heroicon-o-printer')
                    ->color(Color::Cyan)
                    ->url(fn (): string => route('cetak-kartu'))
                    ->openUrlInNewTab(),
            ];
        }

        return [];
    }
}
