<?php

namespace App\Filament\Resources\InstansiResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\InstansiResource;

class CreateInstansi extends CreateRecord
{
    protected static string $resource = InstansiResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
