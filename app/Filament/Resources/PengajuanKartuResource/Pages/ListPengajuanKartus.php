<?php

namespace App\Filament\Resources\PengajuanKartuResource\Pages;

use App\Filament\Resources\PengajuanKartuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPengajuanKartus extends ListRecords
{
    protected static string $resource = PengajuanKartuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
