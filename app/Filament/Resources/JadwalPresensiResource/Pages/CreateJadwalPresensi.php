<?php

namespace App\Filament\Resources\JadwalPresensiResource\Pages;

use App\Filament\Resources\JadwalPresensiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJadwalPresensi extends CreateRecord
{
    protected static string $resource = JadwalPresensiResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
