<?php

namespace App\Filament\Resources\InformasiResource\Pages;

use App\Filament\Resources\InformasiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewInformasi extends ViewRecord
{
    protected static string $resource = InformasiResource::class;

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
