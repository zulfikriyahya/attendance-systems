<?php

namespace App\Filament\Resources\PengajuanKartuResource\Pages;

use App\Filament\Resources\PengajuanKartuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPengajuanKartu extends EditRecord
{
    protected static string $resource = PengajuanKartuResource::class;

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
