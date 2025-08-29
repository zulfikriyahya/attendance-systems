<?php

namespace App\Filament\Resources\InstansiResource\Pages;

use App\Filament\Resources\InstansiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstansi extends EditRecord
{
    protected static string $resource = InstansiResource::class;

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
