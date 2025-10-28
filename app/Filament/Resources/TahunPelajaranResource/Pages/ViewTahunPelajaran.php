<?php

namespace App\Filament\Resources\TahunPelajaranResource\Pages;

use App\Filament\Resources\TahunPelajaranResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewTahunPelajaran extends ViewRecord
{
    protected static string $resource = TahunPelajaranResource::class;

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
