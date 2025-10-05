<?php

namespace App\Filament\Resources\TahunPelajaranResource\Pages;

use App\Models\Enrollment;
use App\Models\TahunPelajaran;
use Illuminate\Support\Facades\Log;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\TahunPelajaranResource;

class CreateTahunPelajaran extends CreateRecord
{
    protected static string $resource = TahunPelajaranResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
