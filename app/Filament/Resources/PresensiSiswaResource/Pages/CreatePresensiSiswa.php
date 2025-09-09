<?php

namespace App\Filament\Resources\PresensiSiswaResource\Pages;

use App\Filament\Resources\PresensiSiswaResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePresensiSiswa extends CreateRecord
{
    protected static string $resource = PresensiSiswaResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
