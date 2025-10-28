<?php

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use App\Filament\Imports\UserImporter;
use Filament\Forms\Components\Checkbox;
use App\Filament\Resources\UserResource;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\UserResource\Widgets\StatsOverview;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderWidgets(): array
    {
        if (Auth::user()->hasRole('super_admin')) {
            return [
                StatsOverview::class,
            ];
        }

        return [];
    }

    protected function getHeaderActions(): array
    {
        if (Auth::user()->hasRole('super_admin')) {
            return [
                CreateAction::make()
                    ->label('Create')
                    ->color(Color::Green)
                    ->size('sm')
                    ->icon('heroicon-o-plus-circle')
                    ->outlined(),
                ImportAction::make('import')
                    ->label('Impor Data')
                    ->color(Color::Blue)
                    ->size('sm')
                    ->icon('heroicon-o-identification')
                    ->outlined()
                    ->requiresConfirmation()
                    ->importer(UserImporter::class)
                    ->visible(fn () => Auth::user()->hasRole('super_admin')),
                Action::make('import-avatar')
                    ->label('Impor Avatar')
                    ->color(Color::Blue)
                    ->size('sm')
                    ->icon('heroicon-o-photo')
                    ->outlined()
                    ->requiresConfirmation()
                    ->visible(fn () => Auth::user()->hasRole('super_admin'))
                    ->form([
                        FileUpload::make('zip_file')
                            ->label('File ZIP Avatar')
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                            ->required()
                            ->helperText('Upload file ZIP yang berisi avatar')
                            ->maxSize(1024000),

                        Checkbox::make('overwrite_existing')
                            ->label('Timpa file yang sudah ada')
                            ->default(true),

                        Checkbox::make('preserve_structure')
                            ->label('Pertahankan struktur folder dalam ZIP')
                            ->default(true)
                            ->helperText('Jika dicentang, struktur folder dalam ZIP akan dipertahankan'),
                    ])
                    ->action(function (array $data) {
                        self::extractZipToStorage($data);
                    }),
            ];
        }

        return [];
    }
}
