<?php

namespace App\Filament\Resources\PegawaiResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\PegawaiResource;
use App\Filament\Resources\PegawaiResource\Widgets\StatsOverview;

class ListPegawais extends ListRecords
{
    protected static string $resource = PegawaiResource::class;
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
                    ->label('Tambah Pegawai')
                    ->outlined()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Emerald),
            ];
        }
        return [];
    }
}
