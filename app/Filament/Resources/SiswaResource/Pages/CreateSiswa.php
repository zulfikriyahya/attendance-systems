<?php

namespace App\Filament\Resources\SiswaResource\Pages;

use App\Filament\Resources\SiswaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSiswa extends CreateRecord
{
    protected static string $resource = SiswaResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
