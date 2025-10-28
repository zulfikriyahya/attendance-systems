<?php

namespace App\Filament\Resources\MataPelajaranResource\Pages;

use App\Filament\Resources\MataPelajaranResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewMataPelajaran extends ViewRecord
{
    protected static string $resource = MataPelajaranResource::class;

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
