<?php

namespace App\Filament\Resources\MataPelajaranResource\Pages;

use App\Filament\Resources\MataPelajaranResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMataPelajaran extends CreateRecord
{
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected static string $resource = MataPelajaranResource::class;
}
