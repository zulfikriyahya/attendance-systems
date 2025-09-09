<?php

namespace App\Filament\Resources\JurusanResource\Pages;

use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Support\Colors\Color;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\JurusanResource;

class ListJurusans extends ListRecords
{
    protected static string $resource = JurusanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Jurusan')
                ->outlined()
                ->icon('heroicon-o-plus-circle')
                ->color(Color::Emerald),
        ];
    }
}
