<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
                ->outlined()
                ->visible(
                    fn ($record) => ! $record->roles->contains('name', 'super_admin')
                ),
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
