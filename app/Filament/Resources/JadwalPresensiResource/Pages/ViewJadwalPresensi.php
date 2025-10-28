<?php

namespace App\Filament\Resources\JadwalPresensiResource\Pages;

use App\Filament\Resources\JadwalPresensiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewJadwalPresensi extends ViewRecord
{
    protected static string $resource = JadwalPresensiResource::class;

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
