<?php

namespace App\Filament\Resources\InformasiResource\Pages;

use App\Filament\Resources\InformasiResource;
use App\Filament\Resources\InformasiResource\Widgets\StatsOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;

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
        if (Auth::user()->hasRole('super_admin')) {
            return [
                CreateAction::make()
                    ->label('Create')
                    ->color(Color::Green)
                    ->size('sm')
                    ->icon('heroicon-o-plus-circle')
                    ->outlined(),
            ];
        }

        return [];
    }
}
