<?php

namespace App\Filament\Resources\PengajuanKartuResource\Pages;

use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Support\Colors\Color;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\PengajuanKartuResource;

class ListPengajuanKartus extends ListRecords
{
    protected static string $resource = PengajuanKartuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Pengajuan')
                ->outlined()
                ->icon('heroicon-o-plus-circle')
                ->color(Color::Emerald),
        ];
    }
}
