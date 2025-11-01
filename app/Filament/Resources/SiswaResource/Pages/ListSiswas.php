<?php

namespace App\Filament\Resources\SiswaResource\Pages;

use App\Filament\Imports\SiswaImporter;
use App\Filament\Resources\SiswaResource;
use App\Filament\Resources\SiswaResource\Widgets\StatsOverview;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;

class ListSiswas extends ListRecords
{
    protected static string $resource = SiswaResource::class;

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
                    ->outlined()
                    ->color('primary')
                    ->icon('heroicon-o-identification')
                    ->importer(SiswaImporter::class),
                Action::make('import-kartu')
                    ->label('Impor Kartu')
                    ->outlined()
                    ->color('primary')
                    ->icon('heroicon-o-photo')
                    ->requiresConfirmation()
                    ->form([
                        FileUpload::make('zip_file')
                            ->label('File ZIP Kartu Siswa')
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                            ->required()
                            ->helperText('Upload file ZIP yang berisi kartu siswa')
                            ->maxSize(102400),

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
