<?php

namespace App\Filament\Imports;

use App\Models\Pegawai;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class PegawaiImporter extends Importer
{
    protected static ?string $model = Pegawai::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('user_id')
                ->requiredMapping()
                ->relationship('user', 'username')
                ->rules(['required']),
            ImportColumn::make('rfid')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('nip')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('jabatan_id')
                ->requiredMapping()
                ->relationship('jabatan', 'nama')
                ->rules(['required', 'max:36']),
            ImportColumn::make('jenisKelamin')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('telepon')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('alamat'),
            ImportColumn::make('status')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean']),
        ];
    }

    public function resolveRecord(): ?Pegawai
    {
        return Pegawai::firstOrNew([
            'nip' => $this->data['nip'],
        ]);

        return new Pegawai;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your pegawai import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
