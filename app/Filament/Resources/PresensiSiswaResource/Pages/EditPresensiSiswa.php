<?php

namespace App\Filament\Resources\PresensiSiswaResource\Pages;

use App\Filament\Resources\PresensiSiswaResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;

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
            ViewAction::make()
                ->label('View')
                ->color(Color::Zinc)
                ->size('sm')
                ->icon('heroicon-o-eye')
                ->outlined(),
            DeleteAction::make()
                ->label('Delete')
                ->color(Color::Red)
                ->size('sm')
                ->icon('heroicon-o-minus-circle')
                ->outlined(),
            ForceDeleteAction::make()
                ->label('Force Delete')
                ->color(Color::Red)
                ->size('sm')
                ->icon('heroicon-o-trash')
                ->outlined(),
            RestoreAction::make()
                ->label('Restore')
                ->color(Color::Blue)
                ->size('sm')
                ->icon('heroicon-o-arrow-path')
                ->outlined(),
        ];
    }
}
