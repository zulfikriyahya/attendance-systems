<?php

namespace App\Filament\Resources\PresensiPegawaiResource\Pages;

use App\Filament\Resources\PresensiPegawaiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPresensiPegawai extends EditRecord
{
    protected static string $resource = PresensiPegawaiResource::class;

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
