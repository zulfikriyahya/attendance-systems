<?php

namespace App\Filament\Resources\JabatanResource\Pages;

use App\Filament\Resources\JabatanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewJabatan extends ViewRecord
{
    protected static string $resource = JabatanResource::class;

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
