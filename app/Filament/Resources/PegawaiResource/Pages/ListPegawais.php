<?php

namespace App\Filament\Resources\PegawaiResource\Pages;

use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Support\Colors\Color;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\PegawaiResource;

class ListPegawais extends ListRecords
{
    protected static string $resource = PegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Pegawai')
                ->outlined()
                ->icon('heroicon-o-plus-circle')
                ->color(Color::Emerald),
        ];
    }
}
