<?php

namespace App\Filament\Resources\InformasiResource\Pages;

use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\InformasiResource;
use App\Filament\Resources\InformasiResource\Widgets\StatsOverview;

class ListInformasis extends ListRecords
{
    protected static string $resource = InformasiResource::class;
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
        return [
            CreateAction::make()
                ->label('Tambah Informasi')
                ->outlined()
                ->icon('heroicon-o-plus-circle')
                ->color(Color::Emerald),
        ];
    }
}
