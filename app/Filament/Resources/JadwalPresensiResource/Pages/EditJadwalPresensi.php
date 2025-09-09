<?php

namespace App\Filament\Resources\JadwalPresensiResource\Pages;

use App\Filament\Resources\JadwalPresensiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJadwalPresensi extends EditRecord
{
    protected static string $resource = JadwalPresensiResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
