<?php

namespace App\Filament\Resources\PresensiPegawaiResource\Pages;

use App\Filament\Resources\PresensiPegawaiResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePresensiPegawai extends CreateRecord
{
    protected static string $resource = PresensiPegawaiResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
