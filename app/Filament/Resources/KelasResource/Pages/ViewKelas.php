<?php

namespace App\Filament\Resources\KelasResource\Pages;

use App\Filament\Resources\KelasResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewKelas extends ViewRecord
{
    protected static string $resource = KelasResource::class;

    protected function getHeaderActions(): array
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
