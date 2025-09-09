<?php

namespace App\Filament\Resources\JabatanResource\Pages;

use App\Filament\Resources\JabatanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJabatan extends CreateRecord
{
    protected static string $resource = JabatanResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
