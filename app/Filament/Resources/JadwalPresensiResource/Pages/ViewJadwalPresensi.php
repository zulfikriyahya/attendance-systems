<?php

namespace App\Filament\Resources\JadwalPresensiResource\Pages;

use App\Filament\Resources\JadwalPresensiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJadwalPresensi extends ViewRecord
{
    protected static string $resource = JadwalPresensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
