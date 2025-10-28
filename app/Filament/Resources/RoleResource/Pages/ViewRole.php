<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    protected function getActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit')
                ->color(Color::Green)
                ->size('sm')
                ->icon('heroicon-o-pencil-square')
                ->outlined(),
        ];
    }
}
