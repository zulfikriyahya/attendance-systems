<?php

namespace App\Filament\Resources\PresensiSiswaResource\Pages;

use App\Filament\Resources\PresensiSiswaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPresensiSiswa extends EditRecord
{
    protected static string $resource = PresensiSiswaResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\ViewAction::make(),
            // Actions\DeleteAction::make(),
            // Actions\ForceDeleteAction::make(),
            // Actions\RestoreAction::make(),
        ];
    }
}
