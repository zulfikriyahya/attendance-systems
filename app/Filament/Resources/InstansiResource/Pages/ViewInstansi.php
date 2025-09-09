<?php

namespace App\Filament\Resources\InstansiResource\Pages;

use App\Filament\Resources\InstansiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInstansi extends ViewRecord
{
    protected static string $resource = InstansiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
