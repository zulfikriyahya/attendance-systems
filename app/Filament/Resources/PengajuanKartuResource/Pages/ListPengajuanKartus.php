<?php

namespace App\Filament\Resources\PengajuanKartuResource\Pages;

use App\Filament\Resources\PengajuanKartuResource;
use App\Filament\Resources\PengajuanKartuResource\Widgets\StatsOverview;
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
                    ->label('Buat Pengajuan')
                    ->outlined()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Emerald),
            ];
        }

        return [];
    }
}
