<?php

namespace App\Filament\Resources\PegawaiResource\Pages;

use App\Filament\Imports\PegawaiImporter;
use App\Filament\Resources\PegawaiResource;
use App\Filament\Resources\PegawaiResource\Widgets\StatsOverview;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;

class ListPegawais extends ListRecords
{
    protected static string $resource = PegawaiResource::class;

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
                    ->size('sm')
                    ->color('primary')
                    ->icon('heroicon-o-identification')
                    ->importer(PegawaiImporter::class),
                Action::make('import-kartu')
                    ->label('Impor Kartu')
                    ->outlined()
                    ->size('sm')
                    ->color('primary')
                    ->icon('heroicon-o-photo')
                    ->requiresConfirmation()
                    ->form([
                        FileUpload::make('zip_file')
                            ->label('File ZIP Kartu Pegawai')
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                            ->required()
                            ->helperText('Upload file ZIP yang berisi kartu pegawai')
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
