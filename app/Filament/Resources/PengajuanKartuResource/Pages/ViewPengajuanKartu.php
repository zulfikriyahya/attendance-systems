<?php

namespace App\Filament\Resources\PengajuanKartuResource\Pages;

use App\Filament\Resources\PengajuanKartuResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewPengajuanKartu extends ViewRecord
{
    protected static string $resource = PengajuanKartuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit')
                ->color(Color::Green)
                ->size('sm')
                ->icon('heroicon-o-pencil-square')
                ->outlined(),
        ];
    }
}
