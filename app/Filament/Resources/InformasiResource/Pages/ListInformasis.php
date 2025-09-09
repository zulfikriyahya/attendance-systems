<?php

namespace App\Filament\Resources\InformasiResource\Pages;

use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Support\Colors\Color;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\InformasiResource;

class ListInformasis extends ListRecords
{
    protected static string $resource = InformasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Informasi')
                ->outlined()
                ->icon('heroicon-o-plus-circle')
                ->color(Color::Emerald),
        ];
    }
}
