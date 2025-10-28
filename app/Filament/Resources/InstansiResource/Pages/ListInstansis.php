<?php

namespace App\Filament\Resources\InstansiResource\Pages;

use App\Filament\Resources\InstansiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;

class ListInstansis extends ListRecords
{
    protected static string $resource = InstansiResource::class;

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
