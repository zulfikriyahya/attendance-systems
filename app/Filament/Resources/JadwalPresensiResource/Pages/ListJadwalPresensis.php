<?php

namespace App\Filament\Resources\JadwalPresensiResource\Pages;

use App\Filament\Resources\JadwalPresensiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJadwalPresensis extends ListRecords
{
    protected static string $resource = JadwalPresensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat')
                ->outlined(),
        ];
    }
}
