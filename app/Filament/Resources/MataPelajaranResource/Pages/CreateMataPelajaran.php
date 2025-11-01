<?php

namespace App\Filament\Resources\MataPelajaranResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\MataPelajaranResource;

class CreateMataPelajaran extends CreateRecord
{
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected static string $resource = MataPelajaranResource::class;
}
