<?php

namespace App\Filament\Resources\JurusanResource\Pages;

use App\Filament\Resources\JurusanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewJurusan extends ViewRecord
{
    protected static string $resource = JurusanResource::class;

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
