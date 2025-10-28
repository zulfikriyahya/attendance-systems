<?php

namespace App\Filament\Resources\MataPelajaranResource\Pages;

use App\Filament\Resources\MataPelajaranResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;

class ListMataPelajarans extends ListRecords
{
    protected static string $resource = MataPelajaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create')
                ->color(Color::Green)
                ->size('sm')
                ->icon('heroicon-o-plus-circle')
                ->outlined(),
        ];
    }
}
