<?php

namespace App\Filament\Resources\MataPelajaranResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Support\Colors\Color;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\MataPelajaranResource;

class EditMataPelajaran extends EditRecord
{
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected static string $resource = MataPelajaranResource::class;

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
