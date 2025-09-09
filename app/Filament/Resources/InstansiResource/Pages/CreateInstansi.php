<?php

namespace App\Filament\Resources\InstansiResource\Pages;

use App\Filament\Resources\InstansiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstansi extends CreateRecord
{
    protected static string $resource = InstansiResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
